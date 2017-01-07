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
     * @param array                       $this_vars
     * @psalm-suppress MixedAssignment
     */
    public function __construct($function, StatementsSource $source, array $this_vars = [])
    {
        if (!$function instanceof PhpParser\Node\Stmt\ClassMethod) {
            throw new \InvalidArgumentException('Must be called with a ClassMethod');
        }

        parent::__construct($function, $source);
    }

    /**
     * @param  string $method_id
     * @return array<int, \Psalm\FunctionLikeParameter>|null
     */
    public static function getMethodParams($method_id)
    {
        if ($method_id = self::getDeclaringMethodId($method_id)) {
            $storage = self::getStorage($method_id);

            if ($storage) {
                return $storage->params;
            }
        }
    }

    /**
     * @param  string $method_id
     * @return boolean
     */
    public static function isVariadic($method_id)
    {
        $method_id = (string)self::getDeclaringMethodId($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        return ClassLikeChecker::$storage[$fq_class_name]->methods[$method_name]->variadic;
    }

    /**
     * @param  string $method_id
     * @return Type\Union|null
     */
    public static function getMethodReturnType($method_id)
    {
        /** @var string */
        $method_id = self::getDeclaringMethodId($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        if (!ClassLikeChecker::isUserDefined($fq_class_name) && FunctionChecker::inCallMap($method_id)) {
            return FunctionChecker::getReturnTypeFromCallMap($method_id);
        }

        $storage = self::getStorage($method_id);

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null');
        }

        if ($storage->return_type) {
            return clone $storage->return_type;
        }

        $class_storage = ClassLikeChecker::$storage[$fq_class_name];

        foreach ($class_storage->overridden_method_ids[$method_name] as $overridden_method_id) {
            $overridden_storage = self::getStorage($overridden_method_id);

            if ($overridden_storage && $overridden_storage->return_type) {
                if ($overridden_storage->return_type->isNull()) {
                    return Type::getVoid();
                }

                return clone $overridden_storage->return_type;
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
        /** @var string */
        $method_id = self::getDeclaringMethodId($method_id);

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

        $class_storage = ClassLikeChecker::$storage[$method->class];

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
        $storage->file_name = $method->getFileName();
        $storage->namespace = $declaring_class->getNamespaceName();
        $class_storage->declaring_method_ids[$method_name] =
            $declaring_class->name . '::' . strtolower((string)$method->getName());

        $class_storage->appearing_method_ids[$method_name] = $class_storage->declaring_method_ids[$method_name];
        $class_storage->overridden_method_ids[$method_name] = [];

        $storage->visibility = $method->isPrivate()
            ? ClassLikeChecker::VISIBILITY_PRIVATE
            : ($method->isProtected() ? ClassLikeChecker::VISIBILITY_PROTECTED : ClassLikeChecker::VISIBILITY_PUBLIC);

        $params = $method->getParameters();

        $method_param_names = [];
        $method_param_types = [];

        $storage->params = [];

        /** @var \ReflectionParameter $param */
        foreach ($params as $param) {
            $param_array = self::getReflectionParamData($param);
            $storage->params[] = $param_array;
            $storage->param_types[$param->name] = $param_array->type;
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
    public static function checkMethodStatic(
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
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     * @return bool|null
     */
    public static function checkMethodExists($method_id, CodeLocation $code_location, array $suppressed_issues)
    {
        if (self::methodExists($method_id)) {
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
     * @param  string $method_id
     * @return bool
     */
    public static function methodExists($method_id)
    {
        // remove trailing backslash if it exists
        $method_id = preg_replace('/^\\\\/', '', $method_id);
        list($fq_class_name, $method_name) = explode('::', $method_id);
        $method_name = strtolower($method_name);
        $method_id = $fq_class_name . '::' . $method_name;

        $old_method_id = null;

        if (!isset(ClassLikeChecker::$storage[$fq_class_name])) {
            throw new \UnexpectedValueException('Storage should exist for ' . $fq_class_name);
        }

        $class_storage = ClassLikeChecker::$storage[$fq_class_name];

        if (isset($class_storage->declaring_method_ids[$method_name])) {
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

        $class_storage = ClassLikeChecker::$storage[$fq_class_name];

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

        list($method_class, $method_name) = explode('::', (string)$method_id);
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

                $file_checker = $source->getFileChecker();

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

        ClassLikeChecker::$storage[$fq_class_name]->declaring_method_ids[$method_name] = $declaring_method_id;
    }

    /**
     * @param string $method_id
     * @param string $appearing_method_id
     * @return void
     */
    public static function setAppearingMethodId($method_id, $appearing_method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        ClassLikeChecker::$storage[$fq_class_name]->appearing_method_ids[$method_name] = $appearing_method_id;
    }

    /**
     * @param  string $method_id
     * @return string|null
     */
    public static function getDeclaringMethodId($method_id)
    {
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

        ClassLikeChecker::$storage[$fq_class_name]->overridden_method_ids[$method_name][] = $overridden_method_id;
    }

    /**
     * @param  string $method_id
     * @return array<string>
     */
    public static function getOverriddenMethodIds($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = ClassLikeChecker::$storage[$fq_class_name];

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

    /**
     * @return void
     */
    public static function clearCache()
    {

    }
}
