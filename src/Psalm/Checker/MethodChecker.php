<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\StatementsSource;
use Psalm\Type;

class MethodChecker extends FunctionLikeChecker
{
    /**
     * @param PhpParser\Node\FunctionLike $function
     * @param StatementsSource            $source
     * @psalm-suppress MixedAssignment
     */
    public function __construct($function, StatementsSource $source)
    {
        if (!$function instanceof PhpParser\Node\Stmt\ClassMethod) {
            throw new \InvalidArgumentException('Must be called with a ClassMethod');
        }

        parent::__construct($function, $source);
    }

    /**
     * @param  string $method_id
     *
     * @return array<int, \Psalm\FunctionLikeParameter>
     */
    public static function getMethodParams(ProjectChecker $project_checker, $method_id)
    {
        if ($method_id = self::getDeclaringMethodId($project_checker, $method_id)) {
            $storage = $project_checker->codebase->getMethodStorage($method_id);

            return $storage->params;
        }

        throw new \UnexpectedValueException('Cannot get method params for ' . $method_id);
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public static function isVariadic(ProjectChecker $project_checker, $method_id)
    {
        $method_id = (string)self::getDeclaringMethodId($project_checker, $method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        return $project_checker->classlike_storage_provider->get($fq_class_name)->methods[$method_name]->variadic;
    }

    /**
     * @param  string $method_id
     * @param  string $self_class
     *
     * @return Type\Union|null
     */
    public static function getMethodReturnType(ProjectChecker $project_checker, $method_id, &$self_class)
    {
        $declaring_method_id = self::getDeclaringMethodId($project_checker, $method_id);

        if (!$declaring_method_id) {
            return null;
        }

        $appearing_method_id = self::getAppearingMethodId($project_checker, $method_id);

        if (!$appearing_method_id) {
            return null;
        }

        list($appearing_fq_class_name, $appearing_method_name) = explode('::', $appearing_method_id);

        if (!ClassLikeChecker::isUserDefined($project_checker, $appearing_fq_class_name)
            && FunctionChecker::inCallMap($appearing_method_id)
        ) {
            return FunctionChecker::getReturnTypeFromCallMap($appearing_method_id);
        }

        $storage = $project_checker->codebase->getMethodStorage($declaring_method_id);

        if ($storage->return_type) {
            $self_class = $appearing_fq_class_name;

            return clone $storage->return_type;
        }

        $class_storage = $project_checker->classlike_storage_provider->get($appearing_fq_class_name);

        if (!isset($class_storage->overridden_method_ids[$appearing_method_name])) {
            return null;
        }

        foreach ($class_storage->overridden_method_ids[$appearing_method_name] as $overridden_method_id) {
            $overridden_storage = $project_checker->codebase->getMethodStorage($overridden_method_id);

            if ($overridden_storage->return_type) {
                if ($overridden_storage->return_type->isNull()) {
                    return Type::getVoid();
                }

                list($fq_overridden_class) = explode('::', $overridden_method_id);

                $overridden_class_storage =
                    $project_checker->classlike_storage_provider->get($fq_overridden_class);

                $overridden_return_type = clone $overridden_storage->return_type;

                if ($overridden_class_storage->template_types) {
                    $generic_types = [];
                    $overridden_return_type->replaceTemplateTypesWithStandins(
                        $overridden_class_storage->template_types,
                        $generic_types
                    );
                }

                $self_class = $overridden_class_storage->name;

                return $overridden_return_type;
            }
        }

        return null;
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public static function getMethodReturnsByRef(ProjectChecker $project_checker, $method_id)
    {
        $method_id = self::getDeclaringMethodId($project_checker, $method_id);

        if (!$method_id) {
            return false;
        }

        list($fq_class_name) = explode('::', $method_id);

        if (!ClassLikeChecker::isUserDefined($project_checker, $fq_class_name)
            && FunctionChecker::inCallMap($method_id)
        ) {
            return false;
        }

        $storage = $project_checker->codebase->getMethodStorage($method_id);

        return $storage->returns_by_ref;
    }

    /**
     * @param  string               $method_id
     * @param  CodeLocation|null    $defined_location
     *
     * @return CodeLocation|null
     */
    public static function getMethodReturnTypeLocation(
        ProjectChecker $project_checker,
        $method_id,
        CodeLocation &$defined_location = null
    ) {
        $method_id = self::getDeclaringMethodId($project_checker, $method_id);

        if ($method_id === null) {
            return null;
        }

        $storage = $project_checker->codebase->getMethodStorage($method_id);

        if (!$storage->return_type_location) {
            $overridden_method_ids = self::getOverriddenMethodIds($project_checker, $method_id);

            foreach ($overridden_method_ids as $overridden_method_id) {
                $overridden_storage = $project_checker->codebase->getMethodStorage($overridden_method_id);

                if ($overridden_storage->return_type_location) {
                    $defined_location = $overridden_storage->return_type_location;
                    break;
                }
            }
        }

        return $storage->return_type_location;
    }

    /**
     * Determines whether a given method is static or not
     *
     * @param  string          $method_id
     * @param  bool            $self_call
     * @param  CodeLocation    $code_location
     * @param  array<string>   $suppressed_issues
     *
     * @return bool
     */
    public static function checkStatic(
        $method_id,
        $self_call,
        ProjectChecker $project_checker,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        /** @var string */
        $method_id = self::getDeclaringMethodId($project_checker, $method_id);

        $storage = $project_checker->codebase->getMethodStorage($method_id);

        if (!$storage->is_static) {
            if ($self_call) {
                if (IssueBuffer::accepts(
                    new NonStaticSelfCall(
                        'Method ' . MethodChecker::getCasedMethodId($project_checker, $method_id) .
                            ' is not static, but is called ' .
                            'using self::',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidStaticInvocation(
                        'Method ' . MethodChecker::getCasedMethodId($project_checker, $method_id) .
                            ' is not static, but is called ' .
                            'statically',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  string       $method_id
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     *
     * @return bool|null
     */
    public static function checkMethodExists(
        ProjectChecker $project_checker,
        $method_id,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if ($project_checker->codebase->methodExists($method_id, $code_location)) {
            return true;
        }

        if (IssueBuffer::accepts(
            new UndefinedMethod('Method ' . $method_id . ' does not exist', $code_location),
            $suppressed_issues
        )) {
            return false;
        }

        return null;
    }

    /**
     * @param  string       $method_id
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     *
     * @return false|null
     */
    public static function checkMethodNotDeprecated(
        ProjectChecker $project_checker,
        $method_id,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $method_id = (string) self::getDeclaringMethodId($project_checker, $method_id);
        $storage = $project_checker->codebase->getMethodStorage($method_id);

        if ($storage->deprecated) {
            if (IssueBuffer::accepts(
                new DeprecatedMethod(
                    'The method ' . MethodChecker::getCasedMethodId($project_checker, $method_id) .
                        ' has been marked as deprecated',
                    $code_location
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param  string           $method_id
     * @param  string|null      $calling_context
     * @param  StatementsSource $source
     *
     * @return bool
     */
    public static function isMethodVisible(
        $method_id,
        $calling_context,
        StatementsSource $source
    ) {
        $project_checker = $source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $declaring_method_id = self::getDeclaringMethodId($project_checker, $method_id);

        if (!$declaring_method_id) {
            $method_name = explode('::', $method_id)[1];

            if ($method_name === '__construct') {
                return true;
            }

            throw new \UnexpectedValueException('$declaring_method_id not expected to be null here');
        }

        $appearing_method_id = self::getAppearingMethodId($project_checker, $method_id);

        $appearing_method_class = null;

        if ($appearing_method_id) {
            list($appearing_method_class) = explode('::', $appearing_method_id);

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $calling_context) {
                return true;
            }
        }

        list($declaring_method_class) = explode('::', $declaring_method_id);

        if ($source->getSource() instanceof TraitChecker && $declaring_method_class === $source->getFQCLN()) {
            return true;
        }

        $storage = $project_checker->codebase->getMethodStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeChecker::VISIBILITY_PUBLIC:
                return true;

            case ClassLikeChecker::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_method_class !== $calling_context) {
                    return false;
                }

                return true;

            case ClassLikeChecker::VISIBILITY_PROTECTED:
                if (!$calling_context) {
                    return false;
                }

                if ($appearing_method_class
                    && $codebase->classExtends($appearing_method_class, $calling_context)
                ) {
                    return true;
                }

                if ($appearing_method_class
                    && !$codebase->classExtends($calling_context, $appearing_method_class)
                ) {
                    return false;
                }
        }

        return true;
    }

    /**
     * @param  string           $method_id
     * @param  string|null      $calling_context
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array            $suppressed_issues
     *
     * @return false|null
     */
    public static function checkMethodVisibility(
        $method_id,
        $calling_context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $project_checker = $source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $declaring_method_id = self::getDeclaringMethodId($project_checker, $method_id);

        if (!$declaring_method_id) {
            $method_name = explode('::', $method_id)[1];

            if ($method_name === '__construct') {
                return null;
            }

            throw new \UnexpectedValueException('$declaring_method_id not expected to be null here');
        }

        $appearing_method_id = self::getAppearingMethodId($project_checker, $method_id);

        $appearing_method_class = null;

        if ($appearing_method_id) {
            list($appearing_method_class) = explode('::', $appearing_method_id);

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $calling_context) {
                return null;
            }
        }

        list($declaring_method_class) = explode('::', $declaring_method_id);

        if ($source->getSource() instanceof TraitChecker && $declaring_method_class === $source->getFQCLN()) {
            return null;
        }

        $storage = $project_checker->codebase->getMethodStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeChecker::VISIBILITY_PUBLIC:
                return null;

            case ClassLikeChecker::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_method_class !== $calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access private method ' .
                                MethodChecker::getCasedMethodId($project_checker, $method_id) .
                                ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }

                return null;

            case ClassLikeChecker::VISIBILITY_PROTECTED:
                if (!$calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' . $method_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                if ($appearing_method_class
                    && $codebase->classExtends($appearing_method_class, $calling_context)
                ) {
                    return null;
                }

                if ($appearing_method_class
                    && !$codebase->classExtends($calling_context, $appearing_method_class)
                ) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' .
                                MethodChecker::getCasedMethodId($project_checker, $method_id) .
                                ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
        }

        return null;
    }

    /**
     * @param string $method_id
     * @param string $declaring_method_id
     *
     * @return void
     */
    public static function setDeclaringMethodId(
        ClassLikeStorageProvider $storage_provider,
        $method_id,
        $declaring_method_id
    ) {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $storage_provider->get($fq_class_name);

        $class_storage->declaring_method_ids[$method_name] = $declaring_method_id;
    }

    /**
     * @param string $method_id
     * @param string $appearing_method_id
     *
     * @return void
     */
    public static function setAppearingMethodId(
        ClassLikeStorageProvider $storage_provider,
        $method_id,
        $appearing_method_id
    ) {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $storage_provider->get($fq_class_name);

        $class_storage->appearing_method_ids[$method_name] = $appearing_method_id;
    }

    /**
     * @param  string $method_id
     *
     * @return string|null
     */
    public static function getDeclaringMethodId(ProjectChecker $project_checker, $method_id)
    {
        $method_id = strtolower($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            return $class_storage->declaring_method_ids[$method_name];
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return $class_storage->overridden_method_ids[$method_name][0];
        }
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     *
     * @param  string $method_id
     *
     * @return string|null
     */
    public static function getAppearingMethodId(ProjectChecker $project_checker, $method_id)
    {
        $method_id = strtolower($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->appearing_method_ids[$method_name])) {
            return $class_storage->appearing_method_ids[$method_name];
        }
    }

    /**
     * @param string  $method_id
     * @param string  $overridden_method_id
     *
     * @return void
     */
    public static function setOverriddenMethodId(
        ClassLikeStorageProvider $storage_provider,
        $method_id,
        $overridden_method_id
    ) {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $storage_provider->get($fq_class_name);

        $class_storage->overridden_method_ids[$method_name][] = $overridden_method_id;
    }

    /**
     * @param  string $method_id
     *
     * @return array<string>
     */
    public static function getOverriddenMethodIds(ProjectChecker $project_checker, $method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->overridden_method_ids[$method_name])) {
            return $class_storage->overridden_method_ids[$method_name];
        }

        return [];
    }

    /**
     * @param  string $original_method_id
     *
     * @return string
     */
    public static function getCasedMethodId(ProjectChecker $project_checker, $original_method_id)
    {
        $method_id = self::getDeclaringMethodId($project_checker, $original_method_id);

        if ($method_id === null) {
            throw new \UnexpectedValueException('Cannot get declaring method id for ' . $original_method_id);
        }

        $storage = $project_checker->codebase->getMethodStorage($method_id);

        list($fq_class_name) = explode('::', $method_id);

        return $fq_class_name . '::' . $storage->cased_name;
    }
}
