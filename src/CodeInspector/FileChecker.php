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

    public static $show_notices = true;

    public function __construct($file_name, array $preloaded_statements = [])
    {
        $this->_file_name = $file_name;

        self::$_file_checkers[$this->_file_name] = $this;

        if ($preloaded_statements) {
            $this->_preloaded_statements = $preloaded_statements;
        }
    }

    public function check($check_classes = true)
    {
        $stmts = $this->_preloaded_statements ?
                    $this->_preloaded_statements :
                    self::getStatements($this->_file_name);

        $leftover_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                if ($check_classes) {
                    (new ClassChecker($stmt, $this, $stmt->name))->check();
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                // @todo check interfaces

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                // @todo check trait

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = implode('\\', $stmt->name->parts);

                $namespace_checker = new NamespaceChecker($stmt, $this);
                $this->_namespace_aliased_classes[$namespace_name] = $namespace_checker->check($check_classes);

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
    }

    public function checkWithClass($class_name)
    {
        $stmts = self::getStatements($this->_file_name);
        $this->_class_name = $class_name;

        $class_method = new PhpParser\Node\Stmt\ClassMethod($class_name, ['stmts' => $stmts]);

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

        return $stmts;
    }

    public static function setCacheDir($cache_dir)
    {
        self::$_cache_dir = $cache_dir;
    }

    public static function shouldCheckVarDumps($file_name)
    {
        return !self::$_var_dump_fn || call_user_func(self::$_var_dump_fn, $file_name);
    }

    public static function shouldCheckClassProperties($file_name, ClassChecker $class_checker = null)
    {
        return !self::$_class_property_fn || call_user_func(self::$_class_property_fn, $file_name, $class_checker);
    }

    public function registerFunction(PhpParser\Node\Stmt\Function_ $function, $absolute_class = null)
    {
        $function_name = ($absolute_class ? $absolute_class . '::' : '') . $function->name;

        $this->_function_params[$function_name] = [];

        foreach ($function->params as $param) {
            $this->_function_params[$function_name][] = $param->byRef;
        }
    }

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

    public function getAbsoluteClass()
    {
        return null;
    }

    public function getClassName()
    {
        return $this->_class_name;
    }

    public function getClassChecker()
    {
        return null;
    }

    public function getClassExtends()
    {
        return null;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    public function isStatic()
    {
        return false;
    }

    public static function getFileCheckerFromFileName($file_name)
    {
        return self::$_file_checkers[$file_name];
    }

    public function hasFunction($function_name)
    {
        return isset($this->_function_params[$function_name]);
    }

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
}
