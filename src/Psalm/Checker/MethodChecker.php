<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;

class MethodChecker extends FunctionLikeChecker
{
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PROTECTED = 2;
    const VISIBILITY_PRIVATE = 3;

    /**
     * @var array<string,string>
     */
    protected static $method_files = [];

    /**
     * @var array<string,array<\Psalm\FunctionLikeParameter>>
     */
    protected static $method_params = [];

    /**
     * @var array<string,string>
     */
    protected static $method_namespaces = [];

    /**
     * @var array<string, Type\Union|null>
     */
    protected static $method_return_types = [];

    /**
     * @var array<string, CodeLocation|null>
     */
    protected static $method_return_type_locations = [];

    /**
     * @var array<string, string>
     */
    protected static $cased_method_ids = [];

    /**
     * @var array<string, bool>
     */
    protected static $static_methods = [];

    /**
     * @var array<string, string>
     */
    protected static $declaring_methods = [];

    /**
     * @var array<string, array<string>>
     */
    protected static $overridden_methods = [];

    /**
     * @var array<string, bool>
     */
    protected static $have_reflected = [];

    /**
     * @var array<string, bool>
     */
    protected static $have_registered = [];

    /**
     * @var array<string, string>
     */
    protected static $method_visibility = [];

    /**
     * @var array<string, array<int, string>>
     */
    protected static $method_suppress = [];

    /**
     * @var array<string, bool>
     */
    protected static $deprecated_methods = [];

    /**
     * A dictionary of variadic methods
     *
     * @var array<string, bool>
     */
    protected static $variadic_methods = [];

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

        $this->registerMethod($function);
        $this->is_static = $function->isStatic();
    }

    /**
     * @param  string $method_id
     * @return array<\Psalm\FunctionLikeParameter>|null
     */
    public static function getMethodParams($method_id)
    {
        self::registerClassMethod($method_id);

        if ($method_id = self::getDeclaringMethodId($method_id)) {
            return self::$method_params[$method_id];
        }
    }

    /**
     * @param  string $method_id
     * @return boolean
     */
    public static function isVariadic($method_id)
    {
        self::registerClassMethod($method_id);

        $method_id = self::getDeclaringMethodId($method_id);

        return isset(self::$variadic_methods[$method_id]);
    }

    /**
     * @param  string $method_id
     * @return Type\Union|null
     */
    public static function getMethodReturnType($method_id)
    {
        self::registerClassMethod($method_id);

        /** @var string */
        $method_id = self::getDeclaringMethodId($method_id);

        $method_class = explode('::', $method_id)[0];

        if (!ClassLikeChecker::isUserDefined($method_class) && FunctionChecker::inCallMap($method_id)) {
            return FunctionChecker::getReturnTypeFromCallMap($method_id);
        }

        if (self::$method_return_types[$method_id]) {
            return clone self::$method_return_types[$method_id];
        }

        $overridden_method_ids = self::getOverriddenMethodIds($method_id);

        foreach ($overridden_method_ids as $overridden_method_id) {
            if (isset(self::$method_return_types[$overridden_method_id])) {
                $implementary_return_type = self::$method_return_types[$overridden_method_id];

                if ($implementary_return_type && $implementary_return_type->isNull()) {
                    return Type::getVoid();
                }

                return $implementary_return_type;
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
        self::registerClassMethod($method_id);

        /** @var string */
        $method_id = self::getDeclaringMethodId($method_id);

        if (!self::$method_return_type_locations[$method_id]) {
            $overridden_method_ids = self::getOverriddenMethodIds($method_id);

            foreach ($overridden_method_ids as $overridden_method_id) {
                if (isset(self::$method_return_type_locations[$overridden_method_id])) {
                    $defined_location = self::$method_return_type_locations[$overridden_method_id];
                    break;
                }
            }
        }

        return self::$method_return_type_locations[$method_id];
    }

    /**
     * @param \ReflectionMethod $method
     * @return null
     */
    public static function extractReflectionMethodInfo(\ReflectionMethod $method)
    {
        $method_id = $method->class . '::' . strtolower((string)$method->name);
        self::$cased_method_ids[$method_id] = $method->class . '::' . $method->name;

        if (strtolower((string)$method->name) === strtolower((string)$method->class)) {
            self::setDeclaringMethodId($method->class . '::__construct', $method_id);
        }

        if (isset(self::$have_reflected[$method_id])) {
            return null;
        }

        /** @var \ReflectionClass */
        $declaring_class = $method->getDeclaringClass();

        self::$have_reflected[$method_id] = true;

        self::$static_methods[$method_id] = $method->isStatic();
        self::$method_files[$method_id] = $method->getFileName();
        self::$method_namespaces[$method_id] = $declaring_class->getNamespaceName();
        self::$declaring_methods[$method_id] = $declaring_class->name . '::' . strtolower((string)$method->getName());
        self::$method_visibility[$method_id] = $method->isPrivate()
            ? self::VISIBILITY_PRIVATE
            : ($method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PUBLIC);

        $params = $method->getParameters();

        $method_param_names = [];
        $method_param_types = [];

        self::$method_params[$method_id] = [];

        /** @var \ReflectionParameter $param */
        foreach ($params as $param) {
            $param_array = self::getReflectionParamArray($param);
            self::$method_params[$method_id][] = $param_array;
            $method_param_names[$param->name] = true;
            $method_param_types[$param->name] = $param_array->type;
        }

        $return_types = null;

        $config = Config::getInstance();

        $return_type = null;

        self::$method_return_type_locations[$method_id] = null;
        self::$method_return_types[$method_id] = $return_type;
        return null;
    }

    /**
     * Determines whether a given method is static or not
     *
     * @param  string               $method_id
     * @param  CodeLocation         $code_location
     * @param  array<int, string>   $suppressed_issues
     * @return bool
     */
    public static function checkMethodStatic($method_id, CodeLocation $code_location, array $suppressed_issues)
    {
        self::registerClassMethod($method_id);

        /** @var string */
        $method_id = self::getDeclaringMethodId($method_id);

        if (!self::$static_methods[$method_id]) {
            if (IssueBuffer::accepts(
                new InvalidStaticInvocation(
                    'Method ' . MethodChecker::getCasedMethodId($method_id) . ' is not static',
                    $code_location
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param PhpParser\Node\Stmt\ClassMethod $method
     * @return null|false
     * @psalm-suppress MixedAssignment
     */
    protected function registerMethod(PhpParser\Node\Stmt\ClassMethod $method)
    {
        $method_id = $this->fq_class_name . '::' . strtolower($method->name);
        $cased_method_id = self::$cased_method_ids[$method_id] = $this->fq_class_name . '::' . $method->name;

        if (strtolower((string)$method->name) === strtolower((string)$this->fq_class_name)) {
            self::setDeclaringMethodId($this->fq_class_name . '::__construct', $method_id);
        }

        if (isset(self::$have_reflected[$method_id]) || isset(self::$have_registered[$method_id])) {
            $this->suppressed_issues = self::$method_suppress[$method_id];

            return null;
        }

        self::$have_registered[$method_id] = true;

        self::$declaring_methods[$method_id] = $method_id;
        self::$static_methods[$method_id] = $method->isStatic();

        self::$method_namespaces[$method_id] = $this->namespace;
        self::$method_files[$method_id] = $this->file_name;

        if ($method->isPrivate()) {
            self::$method_visibility[$method_id] = self::VISIBILITY_PRIVATE;
        } elseif ($method->isProtected()) {
            self::$method_visibility[$method_id] = self::VISIBILITY_PROTECTED;
        } else {
            self::$method_visibility[$method_id] = self::VISIBILITY_PUBLIC;
        }

        self::$method_params[$method_id] = [];

        $method_param_names = [];

        foreach ($method->getParams() as $param) {
            $param_array = $this->getTranslatedParam(
                $param,
                $this
            );

            self::$method_params[$method_id][] = $param_array;
            $method_param_names[$param->name] = $param_array->type;
        }

        $config = Config::getInstance();
        $return_type = null;
        $return_type_location = null;

        $doc_comment = $method->getDocComment();

        self::$method_suppress[$method_id] = [];

        if (isset($method->returnType)) {
            $return_type = Type::parseString(
                is_string($method->returnType)
                    ? $method->returnType
                    : ClassLikeChecker::getFQCLNFromNameObject(
                        $method->returnType,
                        $this->namespace,
                        $this->getAliasedClasses()
                    )
            );

            $return_type_location = new CodeLocation($this->getSource(), $method, false, self::RETURN_TYPE_REGEX);
        }

        if ($doc_comment) {
            $docblock_info = null;

            try {
                $docblock_info = CommentChecker::extractDocblockInfo((string)$doc_comment, $doc_comment->getLine());
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        'Invalid type passed in docblock for ' . $cased_method_id,
                        new CodeLocation($this, $method)
                    )
                )) {
                    return false;
                }
            }

            if ($docblock_info) {
                if ($docblock_info->deprecated) {
                    self::$deprecated_methods[$method_id] = true;
                }

                if ($docblock_info->variadic) {
                    self::$variadic_methods[$method_id] = true;
                }

                $this->suppressed_issues = $docblock_info->suppress;
                self::$method_suppress[$method_id] = $this->suppressed_issues;

                if ($config->use_docblock_types) {
                    if ($docblock_info->return_type) {
                        $docblock_return_type = Type::parseString(
                            $this->fixUpLocalType(
                                (string)$docblock_info->return_type,
                                $this->fq_class_name,
                                $this->namespace,
                                $this->getAliasedClasses()
                            )
                        );

                        if (!$return_type_location) {
                            $return_type_location = new CodeLocation($this->getSource(), $method, true);
                        }

                        if ($return_type && !TypeChecker::hasIdenticalTypes($return_type, $docblock_return_type)) {
                            if (IssueBuffer::accepts(
                                new InvalidDocblock(
                                    'Docblock return type does not match method return type for ' . $this->getMethodId(),
                                    new CodeLocation($this, $method, true)
                                )
                            )) {
                                return false;
                            }
                        } else {
                            $return_type = $docblock_return_type;
                        }

                        $return_type_location->setCommentLine($docblock_info->return_type_line_number);
                    }

                    if ($docblock_info->params) {
                        $this->improveParamsFromDocblock(
                            $docblock_info->params,
                            $method_param_names,
                            self::$method_params[$method_id],
                            new CodeLocation($this, $method, true)
                        );
                    }
                }
            }
        }

        self::$method_return_type_locations[$method_id] = $return_type_location;
        self::$method_return_types[$method_id] = $return_type;
        return null;
    }

    /**
     * @param  string $return_type
     * @param  string $method_id
     * @return string
     */
    protected static function fixUpReturnType($return_type, $method_id)
    {
        if (strpos($return_type, '[') !== false) {
            $return_type = Type::convertSquareBrackets($return_type);
        }

        $return_type_tokens = Type::tokenize($return_type);

        foreach ($return_type_tokens as $i => &$return_type_token) {
            if ($return_type_token[0] === '\\') {
                $return_type_token = substr($return_type_token, 1);
                continue;
            }

            if (in_array($return_type_token, ['<', '>', '|', '?', ',', '{', '}', ':'])) {
                continue;
            }

            if (isset($return_type_token[$i + 1]) && $return_type_token[$i + 1] === ':') {
                continue;
            }

            $return_type_token = Type::fixScalarTerms($return_type_token);

            if ($return_type_token[0] === strtoupper($return_type_token[0])) {
                $fq_class_name = explode('::', $method_id)[0];

                if ($return_type_token === '$this') {
                    $return_type_token = $fq_class_name;
                    continue;
                }

                $return_type_token = FileChecker::getFQCLNFromNameInFile(
                    $return_type_token,
                    self::$method_namespaces[$method_id],
                    self::$method_files[$method_id]
                );
            }
        }

        return implode('', $return_type_tokens);
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
        $method_parts = explode('::', $method_id);
        $method_parts[1] = strtolower($method_parts[1]);
        $method_id = implode('::', $method_parts);

        self::registerClassMethod($method_id);

        $old_method_id = null;

        if (isset(self::$declaring_methods[$method_id])) {
            return true;
        }

        // support checking oldstyle constructors
        if ($method_parts[1] === '__construct') {
            $method_part_parts = explode('\\', $method_parts[0]);
            $old_constructor_name = array_pop($method_part_parts);
            $old_method_id = $method_parts[0] . '::' . $old_constructor_name;
        }

        if (FunctionChecker::inCallMap($method_id) || ($old_method_id && FunctionChecker::inCallMap($method_id))) {
            return true;
        }

        return false;
    }

    /**
     * @param  string $method_id
     * @return void
     */
    public static function registerClassMethod($method_id)
    {
        ClassLikeChecker::registerClass(explode('::', $method_id)[0]);
    }

    /**
     * @param  string       $method_id
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     * @return false|null
     */
    public static function checkMethodNotDeprecated($method_id, CodeLocation $code_location, array $suppressed_issues)
    {
        self::registerClassMethod($method_id);

        if (isset(self::$deprecated_methods[$method_id])) {
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
        self::registerClassMethod($method_id);

        $declared_method_id = self::getDeclaringMethodId($method_id);

        $method_class = explode('::', (string)$method_id)[0];
        $declaring_method_class = explode('::', (string)$declared_method_id)[0];
        $method_name = explode('::', $method_id)[1];

        if (TraitChecker::traitExists($declaring_method_class) && ClassLikeChecker::classUsesTrait($method_class, $declaring_method_class)) {
            return null;
        }

        // if the calling class is the same, we know the method exists, so it must be visible
        if ($method_class === $calling_context) {
            return null;
        }

        if (!isset(self::$method_visibility[$declared_method_id])) {
            if (IssueBuffer::accepts(
                new InaccessibleMethod('Cannot access method ' . $method_id, $code_location),
                $suppressed_issues
            )) {
                return false;
            }
        }

        if ($source->getSource() instanceof TraitChecker && $declaring_method_class === $source->getFQCLN()) {
            return null;
        }

        switch (self::$method_visibility[$declared_method_id]) {
            case self::VISIBILITY_PUBLIC:
                return null;

            case self::VISIBILITY_PRIVATE:
                if (!$calling_context || $declaring_method_class !== $calling_context) {
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

            case self::VISIBILITY_PROTECTED:
                if ($declaring_method_class === $calling_context) {
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

                if (ClassChecker::classExtends($declaring_method_class, $calling_context) &&
                    MethodChecker::methodExists($calling_context . '::' . $method_name)
                ) {
                    return null;
                }

                if (!ClassChecker::classExtends($calling_context, $declaring_method_class)) {
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
        self::$declaring_methods[$method_id] = $declaring_method_id;
    }

    /**
     * @param  string $method_id
     * @return string|null
     */
    public static function getDeclaringMethodId($method_id)
    {
        if (isset(self::$declaring_methods[$method_id])) {
            return self::$declaring_methods[$method_id];
        }

        return null;
    }

    /**
     * @param string  $method_id
     * @param string  $overridden_method_id
     * @return void
     */
    public static function setOverriddenMethodId($method_id, $overridden_method_id)
    {
        self::$overridden_methods[$method_id][] = $overridden_method_id;
    }

    /**
     * @param  string $method_id
     * @return array<string>
     */
    public static function getOverriddenMethodIds($method_id)
    {
        return isset(self::$overridden_methods[$method_id]) ? self::$overridden_methods[$method_id] : [];
    }

    /**
     * @param  string $method_id
     * @return string
     */
    public static function getCasedMethodId($method_id)
    {
        $method_id = self::getDeclaringMethodId($method_id);
        return self::$cased_method_ids[$method_id];
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$method_files = [];
        self::$method_params = [];
        self::$cased_method_ids = [];
        self::$deprecated_methods = [];
        self::$method_namespaces = [];
        self::$method_return_types = [];
        self::$method_return_type_locations = [];
        self::$static_methods = [];
        self::$declaring_methods = [];
        self::$have_reflected = [];
        self::$have_registered = [];
        self::$method_visibility = [];
        self::$overridden_methods = [];
    }
}
