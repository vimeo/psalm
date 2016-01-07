<?php

namespace Vimeo\CodeInspector;

use \PhpParser;
use \PhpParser\Error;
use \PhpParser\ParserFactory;

class FileChecker
{
    protected $_file_name;
    protected $_namespace;
    protected $_aliased_classes = [];

    protected static $_file_checkers = [];

    public function __construct($file_name)
    {
        $this->_file_name = $file_name;
    }

    public function check($check_classes = true)
    {
        $contents = file_get_contents($this->_file_name);

        $cache = \Application::getCache();
        $cache_key = 'parser0' . md5($contents);

        $stmts = $cache->get($cache_key);

        if (!$stmts) {
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

            $stmts = $parser->parse($contents);

            $cache->set($cache_key, $stmts);
        }

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
}
