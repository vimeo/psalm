<?php

namespace CodeInspector;

use PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;

class FileChecker implements StatementsSource
{
    protected $_file_name;
    protected $_namespace;
    protected $_aliased_classes = [];

    protected $_function_params = [];
    protected $_class_name;

    protected $_namespace_aliased_classes = [];

    protected $_preloaded_statements = [];

    protected static $_class_property_fn = null;
    protected static $_var_dump_fn = null;

    protected static $_cache_dir = null;
    protected static $_file_checkers = [];
    protected static $_functions = [];
    protected static $_includes_to_ignore = [];

    protected static $_ignore_check_nulls_pattern = null;
    protected static $_ignore_check_variables_pattern = null;

    public static $show_notices = true;

    public function __construct($file_name, array $preloaded_statements = [])
    {
        $this->_file_name = $file_name;

        self::$_file_checkers[$this->_file_name] = $this;

        if ($preloaded_statements) {
            $this->_preloaded_statements = $preloaded_statements;
        }
    }

    public function check($check_classes = true, $check_class_statements = true)
    {
        $stmts = $this->_preloaded_statements ?
                    $this->_preloaded_statements :
                    self::getStatements($this->_file_name);

        $leftover_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                if ($check_classes) {
                    $class_checker = ClassChecker::getClassCheckerFromClass($stmt->name) ?: new ClassChecker($stmt, $this, $stmt->name);
                    $class_checker->check($check_class_statements);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                // @todo check interfaces

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                if ($check_classes) {
                    $trait_checker = ClassChecker::getClassCheckerFromClass($stmt->name) ?: new TraitChecker($stmt, $this, $stmt->name);
                    $trait_checker->check($check_class_statements);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = implode('\\', $stmt->name->parts);

                $namespace_checker = new NamespaceChecker($stmt, $this);
                $this->_namespace_aliased_classes[$namespace_name] = $namespace_checker->check($check_classes, $check_class_statements);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->_aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }

            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statments_checker = new StatementsChecker($this);
            $existing_vars = [];
            $existing_vars_in_scope = [];
            $statments_checker->check($leftover_stmts, $existing_vars, $existing_vars_in_scope);
        }

        return $stmts;
    }

    public function checkWithClass($class_name, $method_vars = [])
    {
        $stmts = self::getStatements($this->_file_name);

        $class_method = new PhpParser\Node\Stmt\ClassMethod($class_name, ['stmts' => $stmts]);

        if ($method_vars) {
            foreach ($method_vars as $method_var => $type) {
                $class_method->params[] = new PhpParser\Node\Param($method_var, null, $type);
            }
        }

        $class = new PhpParser\Node\Stmt\Class_($class_name);

        $class_checker = new ClassChecker($class, $this, $class_name);

        (new ClassMethodChecker($class_method, $class_checker))->check();
    }

    public static function getAbsoluteClassFromNameInFile($class, $namespace, $file_name)
    {
        if (isset(self::$_file_checkers[$file_name])) {
            $aliased_classes = self::$_file_checkers[$file_name]->getAliasedClasses($namespace);

        } else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false);
            $aliased_classes = $file_checker->getAliasedClasses($namespace);
        }

        return ClassChecker::getAbsoluteClassFromString($class, $namespace, $aliased_classes);
    }

    /**
     * @return array<\PhpParser\Node>
     */
    public static function getStatements($file_name)
    {
        $contents = file_get_contents($file_name);

        $stmts = [];

        $from_cache = false;

        if (self::$_cache_dir) {
            $key = md5($contents);

            $cache_location = self::$_cache_dir . '/' . $key;

            if (is_readable($cache_location)) {
                $stmts = unserialize(file_get_contents($cache_location));
                $from_cache = true;
            }
        }

        if (!$stmts) {
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

            $stmts = $parser->parse($contents);
        }

        if (self::$_cache_dir) {
            if ($from_cache) {
                touch($cache_location);
            } else {
                if (!file_exists(self::$_cache_dir)) {
                    mkdir(self::$_cache_dir);
                }

                file_put_contents($cache_location, serialize($stmts));
            }
        }

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    public static function setCacheDir($cache_dir)
    {
        self::$_cache_dir = $cache_dir;
    }

    /**
     * @return bool
     */
    public static function shouldCheckVarDumps($file_name)
    {
        return !self::$_var_dump_fn || call_user_func(self::$_var_dump_fn, $file_name);
    }

    /**
     * @return bool
     */
    public static function shouldCheckClassProperties($file_name)
    {
        return !self::$_class_property_fn || call_user_func(self::$_class_property_fn, $file_name);
    }

    public function registerFunction(PhpParser\Node\Stmt\Function_ $function)
    {
        $function_name = $function->name;

        $this->_function_params[$function_name] = [];

        foreach ($function->params as $param) {
            $this->_function_params[$function_name][] = $param->byRef;
        }
    }

    /**
     * @return null
     */
    public function getNamespace()
    {
        return null;
    }

    public function getAliasedClasses($namespace_name = null)
    {
        if ($namespace_name && isset($this->_namespace_aliased_classes[$namespace_name])) {
            return $this->_namespace_aliased_classes[$namespace_name];
        }

        return $this->_aliased_classes;
    }

    /**
     * @return null
     */
    public function getAbsoluteClass()
    {
        return null;
    }

    public function getClassName()
    {
        return $this->_class_name;
    }

    /**
     * @return null
     */
    public function getClassChecker()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getParentClass()
    {
        return null;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    public function getSource()
    {
        return null;
    }

    public static function getFileCheckerFromFileName($file_name)
    {
        return self::$_file_checkers[$file_name];
    }

    public static function getClassCheckerFromClass($class_name)
    {
        $file_name = (new \ReflectionClass($class_name))->getFileName();

        if (isset(self::$_file_checkers[$file_name])) {
            $file_checker = self::$_file_checkers[$file_name];
        }
        else {
            $file_checker = new FileChecker($file_name);
        }

        $file_checker->check(true, false);

        return ClassChecker::getClassCheckerFromClass($class_name);
    }

    public function hasFunction($function_name)
    {
        return isset($this->_function_params[$function_name]);
    }

    /**
     * @return bool
     */
    public function isPassedByReference($function_name, $argument_offset)
    {
        return $argument_offset < count($this->_function_params[$function_name]) && $this->_function_params[$function_name][$argument_offset];
    }

    public static function checkClassPropertiesFor(callable $fn)
    {
        self::$_class_property_fn = $fn;
    }

    public static function checkVarDumpsFor(callable $fn)
    {
        self::$_var_dump_fn = $fn;
    }

    public static function ignoreIncludes(array $includes)
    {
        self::$_includes_to_ignore = $includes;
    }

    public static function getIncludesToIgnore()
    {
        return self::$_includes_to_ignore;
    }

    public static function ignoreNullChecksFor($pattern)
    {
        self::$_ignore_check_nulls_pattern = $pattern;
    }

    public static function ignoreVariableChecksFor($pattern)
    {
        self::$_ignore_check_variables_pattern = $pattern;
    }

    public static function shouldCheckVariables($file_name)
    {
        return !self::$_ignore_check_variables_pattern || !preg_match(self::$_ignore_check_variables_pattern, $file_name);
    }

    public static function shouldCheckNulls($file_name)
    {
        return !self::$_ignore_check_nulls_pattern || !preg_match(self::$_ignore_check_nulls_pattern, $file_name);
    }
}
