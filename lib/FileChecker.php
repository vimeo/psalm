<?php

namespace CodeInspector;

use \PhpParser;
use \PhpParser\Error;
use \PhpParser\ParserFactory;

class FileChecker
{
    protected $_file_name;
    protected $_namespace;
    protected $_aliased_classes = [];

    protected static $_cache_dir = null;
    protected static $_file_checkers = [];

    public function __construct($file_name)
    {
        $this->_file_name = $file_name;
    }

    public function check($check_classes = true)
    {
        $stmts = self::_getStatments($this->_file_name);

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                if ($check_classes) {
                    $this->_checkClass($stmt, '');
                }
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $this->_checkNamespace($stmt, $check_classes);
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->_aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }
            }
        }

        self::$_file_checkers[$this->_file_name] = $this;
    }

    public function _checkNamespace(PhpParser\Node\Stmt\Namespace_ $namespace, $check_classes)
    {
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                if ($namespace->name === null) {
                    throw new CodeException('Empty namespace', $this->_file_name, $stmt->getLine());
                }

                $this->_namespace = implode('\\', $namespace->name->parts);

                if ($check_classes) {
                    $this->_checkClass($stmt, $this->_namespace, $this->_aliased_classes);
                }
            }
            else if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->_aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }
            }
        }
    }

    public function _checkClass(PhpParser\Node\Stmt\Class_ $class, $namespace = null)
    {
        (new ClassChecker($class, $namespace, $this->_aliased_classes, $this->_file_name))->check();
    }

    public function getAbsoluteClass($class)
    {
        return ClassChecker::getAbsoluteClass($class, $this->_namespace, $this->_aliased_classes);
    }

    public static function getAbsoluteClassInFile($class, $file_name)
    {
        if (isset(self::$_file_checkers[$file_name])) {
            return self::$_file_checkers[$file_name]->getAbsoluteClass($class);
        }

        $file_checker = new FileChecker($file_name);
        $file_checker->check(false);
        return $file_checker->getAbsoluteClass($class);
    }

    protected static function _getStatments($file_name)
    {
        $contents = file_get_contents($file_name);

        $stmts = [];

        if (self::$_cache_dir) {
            $key = md5($contents);

            $cache_location = self::$_cache_dir . '/' . $key;

            if (is_readable($cache_location)) {
                $stmts = unserialize(file_get_contents($cache_location));
            }
        }

        if (!$stmts) {
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

            $stmts = $parser->parse($contents);
        }

        if (self::$_cache_dir) {
            if (!file_exists(self::$_cache_dir)) {
                mkdir(self::$_cache_dir);
            }

            file_put_contents($cache_location, serialize($stmts));
        }

        return $stmts;
    }

    public static function setCacheDir($cache_dir)
    {
        self::$_cache_dir = $cache_dir;
    }
}
