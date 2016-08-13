<?php

namespace Psalm\Checker;

use PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;

use Psalm\StatementsSource;
use Psalm\Config;
use Psalm\Context;

class FileChecker implements StatementsSource
{
    protected $_real_file_name;
    protected $_short_file_name;
    protected $_namespace;
    protected $_aliased_classes = [];

    protected $_function_params = [];
    protected $_class_name;

    protected $_namespace_aliased_classes = [];

    protected $_preloaded_statements = [];

    protected $_declared_classes = [];

    /**
     * @var array
     */
    protected $_suppressed_issues = [];

    protected static $_cache_dir = null;
    protected static $_file_checkers = [];

    protected static $_class_methods_checked = [];
    protected static $_classes_checked = [];
    protected static $_file_checked = [];

    public static $show_notices = true;

    public function __construct($file_name, array $preloaded_statements = [])
    {
        $this->_real_file_name = $file_name;
        $this->_short_file_name = Config::getInstance()->shortenFileName($file_name);

        self::$_file_checkers[$this->_short_file_name] = $this;
        self::$_file_checkers[$file_name] = $this;

        if ($preloaded_statements) {
            $this->_preloaded_statements = $preloaded_statements;
        }
    }

    public function check($check_classes = true, $check_class_methods = true, Context $file_context = null, $cache = true)
    {
        if ($cache && isset(self::$_class_methods_checked[$this->_real_file_name])) {
            return;
        }

        if ($cache && $check_classes && !$check_class_methods && isset(self::$_classes_checked[$this->_real_file_name])) {
            return;
        }

        if ($cache && !$check_classes && !$check_class_methods && isset(self::$_file_checked[$this->_real_file_name])) {
            return;
        }

        if (!$file_context) {
            $file_context = new Context();
        }

        $stmts = $this->getStatements();

        $leftover_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_
                || $stmt instanceof PhpParser\Node\Stmt\Interface_
                || $stmt instanceof PhpParser\Node\Stmt\Trait_
                || $stmt instanceof PhpParser\Node\Stmt\Namespace_
                || $stmt instanceof PhpParser\Node\Stmt\Use_
            ) {
                if ($leftover_stmts) {
                    $statments_checker = new StatementsChecker($this);
                    $statments_checker->check($leftover_stmts, $file_context);
                    $leftover_stmts = [];
                }

                if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name) ?: new ClassChecker($stmt, $this, $stmt->name);
                        $this->_declared_classes[] = $class_checker->getAbsoluteClass();
                        $class_checker->check($check_class_methods);
                    }

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    // @todo check interfaces

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    if ($check_classes) {
                        $trait_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name) ?: new TraitChecker($stmt, $this, $stmt->name);
                        $trait_checker->check($check_class_methods);
                    }

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                    $namespace_name = implode('\\', $stmt->name->parts);

                    $namespace_checker = new NamespaceChecker($stmt, $this);
                    $this->_namespace_aliased_classes[$namespace_name] = $namespace_checker->check($check_classes, $check_class_methods);
                    $this->_declared_classes = array_merge($namespace_checker->getDeclaredClasses());

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                    foreach ($stmt->uses as $use) {
                        $this->_aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                    }
                }
            }
            else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statments_checker = new StatementsChecker($this);
            $statments_checker->check($leftover_stmts, $file_context);
        }

        if ($check_class_methods) {
            self::$_class_methods_checked[$this->_real_file_name] = true;
        }

        if ($check_classes) {
            self::$_classes_checked[$this->_real_file_name] = true;
        }

        self::$_file_checked[$this->_real_file_name] = true;

        return $stmts;
    }

    public static function getAbsoluteClassFromNameInFile($class, $namespace, $file_name)
    {
        if (isset(self::$_file_checkers[$file_name])) {
            $aliased_classes = self::$_file_checkers[$file_name]->getAliasedClasses($namespace);

        } else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false, false, new Context());
            $aliased_classes = $file_checker->getAliasedClasses($namespace);
        }

        return ClassLikeChecker::getAbsoluteClassFromString($class, $namespace, $aliased_classes);
    }

    /**
     * Gets a list of the classes declared
     * @return array<string>
     */
    public function getDeclaredClasses()
    {
        return $this->_declared_classes;
    }

    /**
     * Gets a list of the classes declared in that file
     * @param  string $file_name
     * @return array<string>
     */
    public static function getDeclaredClassesInFile($file_name)
    {
        if (isset(self::$_file_checkers[$file_name])) {
            $file_checker = self::$_file_checkers[$file_name];
        }
        else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false, false, new Context());
        }

        return $file_checker->getDeclaredClasses();
    }

    /**
     * @return array<\PhpParser\Node>
     */
    protected function getStatements()
    {
        return $this->_preloaded_statements ?
                    $this->_preloaded_statements :
                    self::getStatementsForFile($this->_real_file_name);
    }

    /**
     * @return array<\PhpParser\Node>
     */
    public static function getStatementsForFile($file_name)
    {
        $contents = file_get_contents($file_name);

        $stmts = [];

        $from_cache = false;

        $cache_location = null;

        if (self::$_cache_dir) {
            $key = md5($contents);

            $cache_location = self::$_cache_dir . '/' . $key;

            if (is_readable($cache_location)) {
                $stmts = unserialize(file_get_contents($cache_location));
                $from_cache = true;
            }
        }

        if (!$stmts && $contents) {
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
    public function getClassLikeChecker()
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
        return $this->_short_file_name;
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

    public function getSuppressedIssues()
    {
        return $this->_suppressed_issues;
    }

    public static function getFileCheckerFromFileName($file_name)
    {
        return self::$_file_checkers[$file_name];
    }

    public static function getClassLikeCheckerFromClass($class_name)
    {
        $file_name = (new \ReflectionClass($class_name))->getFileName();

        if (isset(self::$_file_checkers[$file_name])) {
            $file_checker = self::$_file_checkers[$file_name];
        }
        else {
            $file_checker = new FileChecker($file_name);
        }

        $file_checker->check(true, false, null, false);

        return ClassLikeChecker::getClassLikeCheckerFromClass($class_name);
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

    public static function clearCache()
    {
        self::$_file_checkers = [];

        self::$_class_methods_checked = [];
        self::$_classes_checked = [];
        self::$_file_checked = [];
    }
}
