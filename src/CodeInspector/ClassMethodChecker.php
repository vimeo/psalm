<?php

namespace CodeInspector;

use CodeInspector\Exception\UndefinedMethodException;
use CodeInspector\Exception\InaccessibleMethodException;
use CodeInspector\Exception\ReturnTypeException;
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
    protected static $_declaring_class = [];
    protected static $_method_visibility = [];
    protected static $_new_docblocks = [];

    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PROTECTED = 2;
    const VISIBILITY_PRIVATE = 3;

    const TYPE_REGEX = '(\\\?[A-Za-z0-9\<\>\[\]|\\\]+[A-Za-z0-9\<\>\[\]]|\$[a-zA-Z_0-9\<\>\|\[\]]+)';

    public function __construct(PhpParser\Node\FunctionLike $function, StatementsSource $source, array $this_vars = [])
    {
        parent::__construct($function, $source);

        if ($function instanceof PhpParser\Node\Stmt\ClassMethod) {
            $this->_registerMethod($function);
            $this->_is_static = $function->isStatic();
        }
    }

    /**
     * @return void
     */
    public function checkReturnTypes($update_doc_comment = false)
    {
        if (!$this->_function->stmts) {
            return;
        }

        if (!isset(self::$_new_docblocks[$this->_file_name])) {
            self::$_new_docblocks[$this->_file_name] = [];
        }

        $method_id = $this->_absolute_class . '::' . $this->_function->name;

        $existing_return_types = self::getMethodReturnTypes($method_id);

        if ($existing_return_types) {
            $return_types = EffectsAnalyser::getReturnTypes($this->_function->stmts, true);

            if ($return_types && $return_types !== ['mixed']) {
                $simple_existing_return_types = array_map(
                    function ($value) {
                        return preg_replace('/<.*$/', '', $value);
                    },
                    $existing_return_types
                );

                if (count(array_diff($return_types, $simple_existing_return_types)) && count(array_diff($return_types, $existing_return_types))) {
                    if ($update_doc_comment) {
                        $doc_comment = $this->_function->getDocComment();

                        $this->_registerNewDocComment($return_types, $doc_comment);

                        return;
                    }

                    if (ExceptionHandler::accepts(
                        new ReturnTypeException(
                            'The given return type for ' . $method_id . ' is incorrect, expecting ' . implode('|', $return_types),
                            $this->_file_name,
                            $this->_function->getLine()
                        )
                    )) {
                        return false;
                    }
                }
            }

            return;
        }

        if ($update_doc_comment) {
            $return_types = EffectsAnalyser::getReturnTypes($this->_function->stmts, true);

            if ($return_types && $return_types !== ['mixed']) {
                $doc_comment = $this->_function->getDocComment();

                $this->_registerNewDocComment($return_types, $doc_comment);
            }
        }
    }

    /**
     * @param  \PhpParser\Comment\Doc|null $doc_comment
     * @return string
     */
    protected function _registerNewDocComment(array $return_types, \PhpParser\Comment\Doc $doc_comment = null)
    {
        $inverted_aliased_classes = array_flip($this->_aliased_classes);
        $absolute_class = $this->_absolute_class;
        $class_name = array_pop(explode('\\', $absolute_class));

        // add leading namespace separator to classes
        $return_types = array_map(
            function ($return_type) use ($inverted_aliased_classes, $absolute_class, $class_name) {
                $type_tokens = TypeChecker::tokenize($return_type);

                foreach ($type_tokens as &$token) {
                    if ($token === '<' || $token === '>' || $token[0] !== strtoupper($token[0])) {
                        continue;
                    }

                    if (isset($inverted_aliased_classes[$token])) {
                        $token = $inverted_aliased_classes[$token];
                    }
                    else if ($token === $absolute_class) {
                        $token = $class_name;
                    }
                    else {
                        $token = '\\' . $token;
                    }
                }

                return implode('', $type_tokens);
            },
            $return_types
        );

        if ($doc_comment) {
            $parsed_doc_comment = StatementsChecker::parseDocComment($doc_comment->getText());
            $parsed_doc_comment['specials']['return'] = [implode('|', $return_types)];
            $new_doc_comment_text = StatementsChecker::renderDocComment($parsed_doc_comment);
        }
        else {
            $new_doc_comment_text = "/**\n * @return " . implode('|', $return_types) . "\n */";
        }

        $start_at = $doc_comment ? $doc_comment->getLine() : $this->_function->getLine();
        $old_line_count = $doc_comment ? substr_count($doc_comment->getText(), PHP_EOL) + 1 : 0;

        self::$_new_docblocks[$this->_file_name][$start_at] = ['new_text' => $new_doc_comment_text, 'old_line_count' => $old_line_count];
    }

    public static function getMethodParams($method_id)
    {
        self::_populateData($method_id);

        return self::$_method_params[$method_id];
    }

    public static function getMethodReturnTypes($method_id)
    {
        self::_populateData($method_id);

        $return_types = self::$_method_return_types[$method_id];

        return $return_types;
    }

    /**
     * @return void
     */
    public static function extractReflectionMethodInfo($method_id)
    {
        if (isset(self::$_have_reflected[$method_id]) || isset(self::$_have_registered[$method_id])) {
            return;
        }

        $method = new \ReflectionMethod($method_id);
        self::$_have_reflected[$method_id] = true;

        self::$_static_methods[$method_id] = $method->isStatic();
        self::$_method_files[$method_id] = $method->getFileName();
        self::$_method_namespaces[$method_id] = $method->getDeclaringClass()->getNamespaceName();
        self::$_declaring_classes[$method_id] = $method->getDeclaringClass()->name . '::' . $method->getName();
        self::$_method_visibility[$method_id] = $method->isPrivate() ?
                                                    self::VISIBILITY_PRIVATE :
                                                    ($method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PUBLIC);

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

        if (self::$_method_visibility[$method_id] !== self::VISIBILITY_PRIVATE) {
            self::$_method_files[$child_method_id] = self::$_method_files[$method_id];
            self::$_method_params[$child_method_id] = self::$_method_params[$method_id];
            self::$_method_namespaces[$child_method_id] = self::$_method_namespaces[$method_id];
            self::$_method_return_types[$child_method_id] = self::$_method_return_types[$method_id];
            self::$_static_methods[$child_method_id] = self::$_static_methods[$method_id];
            self::$_method_visibility[$child_method_id] = self::$_method_visibility[$method_id];

            self::$_declaring_classes[$child_method_id] = self::$_declaring_classes[$method_id];
            self::$_existing_methods[$child_method_id] = 1;
        }
    }

    /**
     * Determines whether a given method is static or not
     * @param  string  $method_id
     * @return bool
     */
    public static function isGivenMethodStatic($method_id)
    {
        self::_populateData($method_id);

        return self::$_static_methods[$method_id];
    }

    protected function _registerMethod(PhpParser\Node\Stmt\ClassMethod $method)
    {
        $method_id = $this->_absolute_class . '::' . $method->name;

        if (isset(self::$_have_reflected[$method_id]) || isset(self::$_have_registered[$method_id])) {
            return;
        }

        self::$_have_registered[$method_id] = true;

        self::$_declaring_classes[$method_id] = $method_id;
        self::$_static_methods[$method_id] = $method->isStatic();
        self::$_method_comments[$method_id] = $method->getDocComment() ?: '';

        self::$_method_namespaces[$method_id] = $this->_namespace;
        self::$_method_files[$method_id] = $this->_file_name;
        self::$_existing_methods[$method_id] = 1;
        self::$_method_visibility[$method_id] = $method->isPrivate() ?
                                                    self::VISIBILITY_PRIVATE :
                                                    ($method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PUBLIC);

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

                    } elseif ($param->type->parts === ['self']) {
                        $param_type = $this->_absolute_class;

                    } else {
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

    /**
     * @return void
     */
    public static function checkMethodExists($method_id, $file_name, $stmt)
    {
        if (isset(self::$_existing_methods[$method_id])) {
            return;
        }

        $method_parts = explode('::', $method_id);

        if (method_exists($method_parts[0], $method_parts[1])) {
            self::$_existing_methods[$method_id] = 1;
            return;
        }

        if (ExceptionHandler::accepts(
            new UndefinedMethodException('Method ' . $method_id . ' does not exist', $file_name, $stmt->getLine())
        )) {
            return false;
        }
    }

    protected static function _populateData($method_id)
    {
        if (!isset(self::$_have_reflected[$method_id]) && !isset(self::$_have_registered[$method_id])) {
            if (isset(self::$_inherited_methods[$method_id])) {
                self::_copyToChildMethod(self::$_inherited_methods[$method_id], $method_id);
            }
            else {
                self::extractReflectionMethodInfo($method_id);
            }
        }
    }

    /**
     * @return void
     */
    public static function checkMethodVisibility($method_id, $calling_context, $file_name, $line_number)
    {
        self::_populateData($method_id);

        $method_class = explode('::', $method_id)[0];
        $method_name = explode('::', $method_id)[1];

        if (!isset(self::$_method_visibility[$method_id])) {
            if (ExceptionHandler::accepts(
                new InaccessibleMethodException('Cannot access method ' . $method_id, $file_name, $line_number)
            )) {
                return false;
            }
        }

        switch (self::$_method_visibility[$method_id]) {
            case self::VISIBILITY_PUBLIC:
                return;

            case self::VISIBILITY_PRIVATE:
                if (!$calling_context || $method_class !== $calling_context) {
                    if (ExceptionHandler::accepts(
                        new InaccessibleMethodException('Cannot access private method ' . $method_id . ' from context ' . $calling_context, $file_name, $line_number)
                    )) {
                        return false;
                    }
                }
                return;

            case self::VISIBILITY_PROTECTED:
                if ($method_class === $calling_context) {
                    return;
                }

                if (!$calling_context) {
                    if (ExceptionHandler::accepts(
                        new InaccessibleMethodException('Cannot access protected method ' . $method_id, $file_name, $line_number)
                    )) {
                        return false;
                    }
                }

                if (is_subclass_of($method_class, $calling_context) && method_exists($calling_context, $method_name)) {
                    return;
                }

                if (!is_subclass_of($calling_context, $method_class)) {
                    if (ExceptionHandler::accepts(
                        new InaccessibleMethodException('Cannot access protected method ' . $method_id . ' from context ' . $calling_context, $file_name, $line_number)
                    )) {
                        return false;
                    }
                }
        }
    }

    public static function registerInheritedMethod($parent_method_id, $method_id)
    {
        // only register the method if it's not already there
        if (!isset(self::$_declaring_classes[$method_id])) {
            self::$_declaring_classes[$method_id] = $parent_method_id;
        }

        self::$_inherited_methods[$method_id] = $parent_method_id;
    }

    public static function getDeclaringMethod($method_id)
    {
        if (isset(self::$_declaring_classes[$method_id])) {
            return self::$_declaring_classes[$method_id];
        }

        $method_name = explode('::', $method_id)[1];

        $parent_method_id = (new \ReflectionMethod($method_id))->getDeclaringClass()->getName() . '::' . $method_name;

        self::$_declaring_classes[$method_id] = $parent_method_id;

        return $parent_method_id;
    }

    public static function getNewDocblocksForFile($file_name)
    {
        return isset(self::$_new_docblocks[$file_name]) ? self::$_new_docblocks[$file_name] : [];
    }
}
