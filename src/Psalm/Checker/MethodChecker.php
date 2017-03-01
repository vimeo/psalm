<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\MethodStorage;
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
     * @return array<int, \Psalm\FunctionLikeParameter>
     */
    public static function getMethodParams($method_id)
    {
        if ($method_id = self::getDeclaringMethodId($method_id)) {
            $storage = self::getStorage($method_id);

            if ($storage) {
                return $storage->params;
            }
        }

        throw new \UnexpectedValueException('Cannot get method params for ' . $method_id);
    }

    /**
     * @param  string $method_id
     * @return boolean
     */
    public static function isVariadic($method_id)
    {
        $method_id = (string)self::getDeclaringMethodId($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $fq_class_name = strtolower($fq_class_name);

        return ClassLikeChecker::$storage[$fq_class_name]->methods[$method_name]->variadic;
    }

    /**
     * @param  string                       $method_id
     * @return Type\Union|null
     */
    public static function getMethodReturnType($method_id) {
        $method_id = self::getDeclaringMethodId($method_id);

        if (!$method_id) {
            return null;
        }

        list($fq_class_name, $method_name) = explode('::', $method_id);

        if (!ClassLikeChecker::isUserDefined($fq_class_name) && FunctionChecker::inCallMap($method_id)) {
            return FunctionChecker::getReturnTypeFromCallMap($method_id);
        }

        $storage = self::getStorage($method_id);

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null');
        }

        if ($storage->return_type) {
            return $storage->return_type;
        }

        $fq_class_name = strtolower($fq_class_name);

        $class_storage = ClassLikeChecker::$storage[$fq_class_name];

        foreach ($class_storage->overridden_method_ids[$method_name] as $overridden_method_id) {
            $overridden_storage = self::getStorage($overridden_method_id);

            if ($overridden_storage && $overridden_storage->return_type) {
                if ($overridden_storage->return_type->isNull()) {
                    return Type::getVoid();
                }

                return $overridden_storage->return_type;
            }
        }

        return null;
    }

    /**
     * @param  string               $method_id
     * @param  CodeLocation|null    $defined_location
     * @return CodeLocation|null
     */
    public static function getMethodReturnTypeLocation($method_id, CodeLocation &$defined_location = null)
    {
        $method_id = self::getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return null;
        }

        $storage = self::getStorage($method_id);

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null');
        }

        if (!$storage->return_type_location) {
            $overridden_method_ids = self::getOverriddenMethodIds($method_id);

            foreach ($overridden_method_ids as $overridden_method_id) {
                $overridden_storage = self::getStorage($overridden_method_id);

                if ($overridden_storage && $overridden_storage->return_type_location) {
                    $defined_location = $overridden_storage->return_type_location;
                    break;
                }
            }
        }

        return $storage->return_type_location;
    }

    /**
     * @param \ReflectionMethod $method
     * @return null
     */
    public static function extractReflectionMethodInfo(\ReflectionMethod $method)
    {
        $method_name = strtolower($method->getName());

        $class_storage = ClassLikeChecker::$storage[strtolower($method->class)];

        if (isset($class_storage->methods[strtolower($method_name)])) {
            return;
        }

        $method_id = $method->class . '::' . $method_name;

        $storage = $class_storage->methods[strtolower($method_name)] = new MethodStorage();

        $storage->cased_name = $method->name;

        if (strtolower((string)$method->name) === strtolower((string)$method->class)) {
            self::setDeclaringMethodId($method->class . '::__construct', $method->class . '::' . $method_name);
            self::setAppearingMethodId($method->class . '::__construct', $method->class . '::' . $method_name);
        }

        /** @var \ReflectionClass */
        $declaring_class = $method->getDeclaringClass();

        $storage->is_static = $method->isStatic();
        $storage->namespace = $declaring_class->getNamespaceName();
        $class_storage->declaring_method_ids[$method_name] =
            $declaring_class->name . '::' . strtolower((string)$method->getName());

        $class_storage->appearing_method_ids[$method_name] = $class_storage->declaring_method_ids[$method_name];
        $class_storage->overridden_method_ids[$method_name] = [];

        $storage->visibility = $method->isPrivate()
            ? ClassLikeChecker::VISIBILITY_PRIVATE
            : ($method->isProtected() ? ClassLikeChecker::VISIBILITY_PROTECTED : ClassLikeChecker::VISIBILITY_PUBLIC);


        $possible_params = FunctionChecker::getParamsFromCallMap($method_id);

        if ($possible_params === null) {
            $params = $method->getParameters();

            $storage->params = [];

            /** @var \ReflectionParameter $param */
            foreach ($params as $param) {
                $param_array = self::getReflectionParamData($param);
                $storage->params[] = $param_array;
                $storage->param_types[$param->name] = $param_array->type;
            }
        } else {
            $storage->params = $possible_params[0];
        }

        $storage->required_param_count = 0;

        foreach ($storage->params as $i => $param) {
            if (!$param->is_optional) {
                $storage->required_param_count = $i + 1;
            }
        }

        return null;
    }

    /**
     * Determines whether a given method is static or not
     *
     * @param  string          $method_id
     * @param  bool            $self_call
     * @param  CodeLocation    $code_location
     * @param  array<string>   $suppressed_issues
     * @return bool
     */
    public static function checkStatic(
        $method_id,
        $self_call,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        /** @var string */
        $method_id = self::getDeclaringMethodId($method_id);

        $storage = self::getStorage($method_id);

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null');
        }

        if (!$storage->is_static) {
            if ($self_call) {
                if (IssueBuffer::accepts(
                    new NonStaticSelfCall(
                        'Method ' . MethodChecker::getCasedMethodId($method_id) . ' is not static, but is called using self::',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidStaticInvocation(
                        'Method ' . MethodChecker::getCasedMethodId($method_id) . ' is not static, but is called statically',
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
     * @param  FileChecker  $file_checker
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     * @return bool|null
     */
    public static function checkMethodExists(
        $method_id,
        FileChecker $file_checker,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if (self::methodExists($method_id, $file_checker, $code_location)) {
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
     * Whether or not a given method exists
     *
     * @param  string       $method_id
     * @param  FileChecker  $file_checker
     * @param  CodeLocation|null $code_location
     * @return bool
     */
    public static function methodExists(
        $method_id,
        FileChecker $file_checker,
        CodeLocation $code_location = null
    ) {
        // remove trailing backslash if it exists
        $method_id = preg_replace('/^\\\\/', '', $method_id);
        list($fq_class_name, $method_name) = explode('::', $method_id);
        $method_name = strtolower($method_name);
        $method_id = $fq_class_name . '::' . $method_name;

        $old_method_id = null;

        $fq_class_name_lower = strtolower($fq_class_name);

        if (!isset(ClassLikeChecker::$storage[$fq_class_name_lower])) {
            throw new \UnexpectedValueException('Storage should exist for ' . $fq_class_name);
        }

        $class_storage = ClassLikeChecker::$storage[$fq_class_name_lower];

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            if ($file_checker->project_checker->collect_references && $code_location) {
                $declaring_method_id = $class_storage->declaring_method_ids[$method_name];
                list($declaring_method_class, $declaring_method_name) = explode('::', $declaring_method_id);

                $declaring_class_storage = ClassLikeChecker::$storage[strtolower($declaring_method_class)];
                $declaring_method_storage = $declaring_class_storage->methods[strtolower($declaring_method_name)];
                if ($declaring_method_storage->referencing_locations === null) {
                    $declaring_method_storage->referencing_locations = [];
                }
                $declaring_method_storage->referencing_locations[$file_checker->getFilePath()][] = $code_location;

                if (isset($declaring_class_storage->overridden_method_ids[$declaring_method_name])) {
                    $overridden_method_ids = $declaring_class_storage->overridden_method_ids[$declaring_method_name];

                    foreach ($overridden_method_ids as $overridden_method_id) {
                        list($overridden_method_class, $overridden_method_name) = explode('::', $overridden_method_id);

                        $class_storage = ClassLikeChecker::$storage[strtolower($overridden_method_class)];
                        $method_storage = $class_storage->methods[strtolower($overridden_method_name)];
                        if ($method_storage->referencing_locations === null) {
                            $method_storage->referencing_locations = [];
                        }
                        $method_storage->referencing_locations[$file_checker->getFilePath()][] = $code_location;
                    }
                }
            }

            return true;
        }

        // support checking oldstyle constructors
        if ($method_name === '__construct') {
            $method_name_parts = explode('\\', $fq_class_name);
            $old_constructor_name = array_pop($method_name_parts);
            $old_method_id = $fq_class_name . '::' . $old_constructor_name;
        }

        if (FunctionChecker::inCallMap($method_id) || ($old_method_id && FunctionChecker::inCallMap($method_id))) {
            return true;
        }

        return false;
    }

    /**
     * @param  string $method_id
     * @return MethodStorage
     */
    public static function getStorage($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $fq_class_name_lower = strtolower($fq_class_name);

        if (!isset(ClassLikeChecker::$storage[$fq_class_name_lower])) {
            throw new \UnexpectedValueException('$class_storage should not be null for ' . $method_id);
        }

        $class_storage = ClassLikeChecker::$storage[$fq_class_name_lower];

        if (!isset($class_storage->methods[strtolower($method_name)])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $method_id);
        }

        return $class_storage->methods[strtolower($method_name)];
    }

    /**
     * @param  string       $method_id
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     * @return false|null
     */
    public static function checkMethodNotDeprecated($method_id, CodeLocation $code_location, array $suppressed_issues)
    {
        $method_id = (string) self::getDeclaringMethodId($method_id);
        $storage = self::getStorage($method_id);

        if ($storage->deprecated) {
            if (IssueBuffer::accepts(
                new DeprecatedMethod(
                    'The method ' . MethodChecker::getCasedMethodId($method_id) . ' has been marked as deprecated',
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
     * @param  CodeLocation     $code_location
     * @param  array            $suppressed_issues
     * @return false|null
     */
    public static function checkMethodVisibility(
        $method_id,
        $calling_context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $declaring_method_id = self::getDeclaringMethodId($method_id);
        $appearing_method_id = self::getAppearingMethodId($method_id);

        list($declaring_method_class) = explode('::', (string)$declaring_method_id);
        list($appearing_method_class) = explode('::', (string)$appearing_method_id);

        // if the calling class is the same, we know the method exists, so it must be visible
        if ($appearing_method_class === $calling_context) {
            return null;
        }

        if ($source->getSource() instanceof TraitChecker && $declaring_method_class === $source->getFQCLN()) {
            return null;
        }

        $storage = self::getStorage((string)$declaring_method_id);

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null');
        }

        switch ($storage->visibility) {
            case ClassLikeChecker::VISIBILITY_PUBLIC:
                return null;

            case ClassLikeChecker::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_method_class !== $calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access private method ' . MethodChecker::getCasedMethodId($method_id) .
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
                if ($appearing_method_class === $calling_context) {
                    return null;
                }

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

                if (ClassChecker::classExtends($appearing_method_class, $calling_context)) {
                    return null;
                }

                if (!ClassChecker::classExtends($calling_context, $appearing_method_class)) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' . MethodChecker::getCasedMethodId($method_id) .
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
     * @return void
     */
    public static function setDeclaringMethodId($method_id, $declaring_method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        ClassLikeChecker::$storage[strtolower($fq_class_name)]->declaring_method_ids[$method_name] = $declaring_method_id;
    }

    /**
     * @param string $method_id
     * @param string $appearing_method_id
     * @return void
     */
    public static function setAppearingMethodId($method_id, $appearing_method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        ClassLikeChecker::$storage[strtolower($fq_class_name)]->appearing_method_ids[$method_name] = $appearing_method_id;
    }

    /**
     * @param  string $method_id
     * @return string|null
     */
    public static function getDeclaringMethodId($method_id)
    {
        $method_id = strtolower($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        if (!isset(ClassLikeChecker::$storage[$fq_class_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $fq_class_name);
        }

        if (isset(ClassLikeChecker::$storage[$fq_class_name]->declaring_method_ids[$method_name])) {
            return ClassLikeChecker::$storage[$fq_class_name]->declaring_method_ids[$method_name];
        }
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     *
     * @param  string $method_id
     * @return string|null
     */
    public static function getAppearingMethodId($method_id)
    {
        $method_id = strtolower($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        if (!isset(ClassLikeChecker::$storage[$fq_class_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $fq_class_name);
        }

        if (isset(ClassLikeChecker::$storage[$fq_class_name]->appearing_method_ids[$method_name])) {
            return ClassLikeChecker::$storage[$fq_class_name]->appearing_method_ids[$method_name];
        }
    }

    /**
     * @param string  $method_id
     * @param string  $overridden_method_id
     * @return void
     */
    public static function setOverriddenMethodId($method_id, $overridden_method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        ClassLikeChecker::$storage[strtolower($fq_class_name)]->overridden_method_ids[$method_name][] = $overridden_method_id;
    }

    /**
     * @param  string $method_id
     * @return array<string>
     */
    public static function getOverriddenMethodIds($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

        if (isset($class_storage->overridden_method_ids[$method_name])) {
            return $class_storage->overridden_method_ids[$method_name];
        }

        return [];
    }

    /**
     * @param  string $original_method_id
     * @return string
     */
    public static function getCasedMethodId($original_method_id)
    {
        $method_id = self::getDeclaringMethodId($original_method_id);

        if ($method_id === null) {
            throw new \UnexpectedValueException('Cannot get declaring method id for ' . $original_method_id);
        }

        $storage = self::getStorage($method_id);

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null');
        }

        list($fq_class_name) = explode('::', $method_id);

        return $fq_class_name . '::' .$storage->cased_name;
    }
}
