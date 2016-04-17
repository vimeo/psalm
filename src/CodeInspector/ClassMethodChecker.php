<?php

namespace CodeInspector;

use CodeInspector\Exception\UndefinedMethodException;
use PhpParser;

class ClassMethodChecker extends FunctionChecker
{
    protected static $_method_comments = [];
    protected static $_method_files = [];
    protected static $_method_params = [];
    protected static $_method_namespaces = [];
    protected static $_method_return_types = [];
    protected static $_static_methods = [];
    protected static $_declaring_classes = [];
    protected static $_existing_methods = [];
    protected static $_have_reflected = [];
    protected static $_have_registered = [];
    protected static $_method_custom_calls = [];
    protected static $_inherited_methods = [];

    const TYPE_REGEX = '(\\\?[A-Za-z0-9\<\>\[\]|\\\]+[A-Za-z0-9\<\>\[\]]|\$[a-zA-Z_0-9\<\>\[\]]+)';

    public function __construct(PhpParser\Node\FunctionLike $function, StatementsSource $source)
    {
        parent::__construct($function, $source);

        $this->_registerMethod($function);
    }

    public static function getMethodParams($method_id)
    {
        if (!isset(self::$_method_params[$method_id])) {
            if (isset(self::$_inherited_methods[$method_id])) {
                self::_copyToChildMethod(self::$_inherited_methods[$method_id], $method_id);
            }
            else {
                self::extractReflectionMethodInfo($method_id);
            }
        }

        return self::$_method_params[$method_id];
    }

    public static function getMethodReturnTypes($method_id)
    {
        if (!isset(self::$_method_return_types[$method_id])) {
            if (isset(self::$_inherited_methods[$method_id])) {
                self::_copyToChildMethod(self::$_inherited_methods[$method_id], $method_id);
            }
            else {
                self::extractReflectionMethodInfo($method_id);
            }
        }

        $return_types = self::$_method_return_types[$method_id];

        return $return_types;
    }

    public static function extractReflectionMethodInfo($method_id)
    {
        if (isset(self::$_have_reflected[$method_id])) {
            return;
        }

        $method = new \ReflectionMethod($method_id);
        self::$_have_reflected[$method_id] = true;

        self::$_static_methods[$method_id] = $method->isStatic();
        self::$_method_files[$method_id] = $method->getFileName();
        self::$_method_namespaces[$method_id] = $method->getDeclaringClass()->getNamespaceName();
        self::$_declaring_classes[$method_id] = $method->getDeclaringClass()->name;

        $params = $method->getParameters();

        self::$_method_params[$method_id] = [];
        foreach ($params as $param) {
            $param_type = null;

            if ($param->isArray()) {
                $param_type = 'array';

            } elseif ($param->getClass() && self::$_method_files[$method_id]) {
                $param_type = $param->getClass()->getName();
            }

            $is_nullable = false;

            try {
                $is_nullable = $param->getDefaultValue() === null;
            }
            catch (\ReflectionException $e) {
                // do nothing
            }

            self::$_method_params[$method_id][] = [
                'name' => $param->getName(),
                'by_ref' => $param->isPassedByReference(),
                'type' => $param_type,
                'is_nullable' => $is_nullable
            ];
        }

        $return_types = [];

        $comments = StatementsChecker::parseDocComment($method->getDocComment() ?: '');

        if ($comments) {
            if (isset($comments['specials']['return'])) {
                $return_blocks = explode(' ', $comments['specials']['return'][0]);
                foreach ($return_blocks as $block) {
                    if ($block && preg_match('/^' . self::TYPE_REGEX . '$/', $block)) {
                        $return_types = explode('|', $block);
                        break;
                    }
                }
            }

            if (isset($comments['specials']['call'])) {
                self::$_method_custom_calls[$method_id] = [];

                $call_blocks = $comments['specials']['call'];
                foreach ($comments['specials']['call'] as $block) {
                    if ($block) {
                        self::$_method_custom_calls[$method_id][] = trim($block);
                    }
                }
            }

            $return_types = array_filter($return_types, function ($entry) {
                return !empty($entry) && $entry !== '[type]';
            });

            if ($return_types) {
                foreach ($return_types as &$return_type) {
                    $return_type = self::_fixUpReturnType($return_type, $method_id);
                }
            }
        }

        self::$_method_return_types[$method_id] = $return_types;
    }

    protected static function _copyToChildMethod($method_id, $child_method_id)
    {
        if (!isset(self::$_have_registered[$method_id]) && !isset(self::$_have_reflected[$method_id])) {
            self::extractReflectionMethodInfo($method_id);
        }

        self::$_method_files[$child_method_id] = self::$_method_files[$method_id];
        self::$_method_params[$child_method_id] = self::$_method_params[$method_id];
        self::$_method_namespaces[$child_method_id] = self::$_method_namespaces[$method_id];
        self::$_method_return_types[$child_method_id] = self::$_method_return_types[$method_id];
        self::$_static_methods[$child_method_id] = self::$_static_methods[$method_id];

        self::$_declaring_classes[$child_method_id] = self::$_declaring_classes[$method_id];
        self::$_existing_methods[$child_method_id] = 1;
    }

    /**
     * Determines whether a given method is static or not
     * @param  string  $method_id
     * @return boolean
     */
    public static function isGivenMethodStatic($method_id)
    {
        if (!isset(self::$_static_methods[$method_id])) {
            if (isset(self::$_inherited_methods[$method_id])) {
                self::_copyToChildMethod(self::$_inherited_methods[$method_id], $method_id);
            }
            else {
                self::extractReflectionMethodInfo($method_id);
            }
        }

        return self::$_static_methods[$method_id];
    }

    protected function _registerMethod(PhpParser\Node\Stmt\ClassMethod $method)
    {
        $method_id = $this->_absolute_class . '::' . $method->name;
        self::$_have_registered[$method_id] = true;

        self::$_declaring_classes[$method_id] = $this->_absolute_class;
        self::$_static_methods[$method_id] = $method->isStatic();
        self::$_method_comments[$method_id] = $method->getDocComment() ?: '';

        self::$_method_namespaces[$method_id] = $this->_namespace;
        self::$_method_files[$method_id] = $this->_file_name;
        self::$_existing_methods[$method_id] = 1;

        $comments = StatementsChecker::parseDocComment($method->getDocComment());

        $return_types = [];

        if (isset($comments['specials']['return'])) {
            $return_blocks = explode(' ', $comments['specials']['return'][0]);
            foreach ($return_blocks as $block) {
                if ($block) {
                    if ($block && preg_match('/^' . self::TYPE_REGEX . '$/', $block)) {
                        $return_types = explode('|', $block);
                        break;
                    }
                }
            }
        }

        if (isset($comments['specials']['call'])) {
            self::$_method_custom_calls[$method_id] = [];

            $call_blocks = $comments['specials']['call'];
            foreach ($comments['specials']['call'] as $block) {
                if ($block) {
                    self::$_method_custom_calls[$method_id][] = trim($block);
                }
            }
        }

        $return_types = array_filter($return_types, function ($entry) {
            return !empty($entry) && $entry !== '[type]';
        });

        foreach ($return_types as &$return_type) {
            $return_type = $this->_fixUpLocalReturnType($return_type, $method_id, $this->_namespace, $this->_aliased_classes);
        }

        self::$_method_return_types[$method_id] = $return_types;

        self::$_method_params[$method_id] = [];

        foreach ($method->getParams() as $param) {
            $param_type = null;

            if ($param->type) {
                if (is_string($param->type)) {
                    $param_type = $param->type;
                }
                else {
                    if ($param->type instanceof PhpParser\Node\Name\FullyQualified) {
                        $param_type = implode('\\', $param->type->parts);
                    }
                    else {
                        $param_type = ClassChecker::getAbsoluteClassFromString(implode('\\', $param->type->parts), $this->_namespace, $this->_aliased_classes);
                    }
                }
            }

            $is_nullable = $param->default !== null &&
                            $param->default instanceof \PhpParser\Node\Expr\ConstFetch &&
                            $param->default->name instanceof PhpParser\Node\Name &&
                            $param->default->name->parts = ['null'];

            self::$_method_params[$method_id][] = [
                'name' => $param->name,
                'by_ref' => $param->byRef,
                'type' => $param_type,
                'is_nullable' => $is_nullable
            ];
        }
    }

    protected static function _fixUpLocalReturnType($return_type, $method_id, $namespace, $aliased_classes)
    {
        if (strpos($return_type, '[') !== false) {
            $return_type = TypeChecker::convertSquareBrackets($return_type);
        }

        $return_type_tokens = TypeChecker::tokenize($return_type);

        foreach ($return_type_tokens as &$return_type_token) {
            if ($return_type_token[0] === '\\') {
                $return_type_token = substr($return_type_token, 1);
                continue;
            }

            if (in_array($return_type_token, ['<', '>'])) {
                continue;
            }

            if ($return_type_token[0] === strtoupper($return_type_token[0])) {
                $absolute_class = explode('::', $method_id)[0];

                if ($return_type === '$this') {
                    $return_type_token = $absolute_class;
                    continue;
                }

                $return_type_token = ClassChecker::getAbsoluteClassFromString($return_type_token, $namespace, $aliased_classes);
            }
        }

        return implode('', $return_type_tokens);
    }

    protected static function _fixUpReturnType($return_type, $method_id)
    {
        if (strpos($return_type, '[') !== false) {
            $return_type = TypeChecker::convertSquareBrackets($return_type);
        }

        $return_type_tokens = TypeChecker::tokenize($return_type);

        foreach ($return_type_tokens as &$return_type_token) {
            if ($return_type_token[0] === '\\') {
                $return_type_token = substr($return_type_token, 1);
                continue;
            }

            if (in_array($return_type_token, ['<', '>'])) {
                continue;
            }

            if ($return_type_token[0] === strtoupper($return_type_token[0])) {
                $absolute_class = explode('::', $method_id)[0];

                if ($return_type_token === '$this') {
                    $return_type_token = $absolute_class;
                    continue;
                }

                $return_type_token = FileChecker::getAbsoluteClassFromNameInFile($return_type_token, self::$_method_namespaces[$method_id], self::$_method_files[$method_id]);
            }
        }

        return implode('', $return_type_tokens);
    }

    public static function checkMethodExists($method_id, $file_name, $stmt)
    {
        if (isset(self::$_existing_methods[$method_id])) {
            return;
        }

        try {
            new \ReflectionMethod($method_id);
            self::$_existing_methods[$method_id] = 1;
            return;

        } catch (\ReflectionException $e) {
            throw new UndefinedMethodException('Method ' . $method_id . ' does not exist', $file_name, $stmt->getLine());
        }
    }

    public static function registerInheritedMethod($parent_method_id, $method_id)
    {
        self::$_inherited_methods[$method_id] = $parent_method_id;
        self::$_existing_methods[$method_id] = 1;
    }
}
