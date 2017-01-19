<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Context;
use Psalm\Storage\ClassLikeStorage;

class TraitChecker extends ClassLikeChecker
{
    /**
     * @var array<string, string>
     */
    protected $method_map = [];

    /**
     * @var array<string, string>
     */
    protected static $trait_names = [];

    /**
     * @var array<string, bool>
     */
    protected static $existing_traits = [];

    /**
     * @param   PhpParser\Node\Stmt\ClassLike   $class
     * @param   StatementsSource                $source
     * @param   string                          $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $fq_class_name)
    {
        if (!$class instanceof PhpParser\Node\Stmt\Trait_) {
            throw new \InvalidArgumentException('Trait checker must be passed a trait');
        }

        $this->source = $source;
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;

        $fq_class_name_lower = strtolower($fq_class_name);

        $project_checker = $source->getFileChecker()->project_checker;

        if (!isset(self::$storage[$fq_class_name_lower])) {
            self::$storage[$fq_class_name_lower] = $storage = new ClassLikeStorage();
            $storage->file_name = $this->source->getFileName();
            $storage->file_path = $this->source->getFilePath();
        }

        self::$trait_names[$fq_class_name_lower] = $fq_class_name;

        self::$trait_checkers[$fq_class_name] = $this;
    }

    /**
     * @param   Context|null    $class_context
     * @param   Context|null    $global_context
     * @return void
     */
    public function visit(
        Context $class_context = null,
        Context $global_context = null
    ) {
        if (!$class_context) {
            throw new \InvalidArgumentException('TraitChecker::check must be called with a $class_context');
        }

        parent::visit($class_context, $global_context);
    }

    /**
     * @param   array<string, string> $method_map
     * @return  void
     */
    public function setMethodMap(array $method_map)
    {
        $this->method_map = $method_map;
    }

    /**
     * @param  string $method_name
     * @return string
     */
    protected function getMappedMethodName($method_name)
    {
        if (isset($this->method_map[$method_name])) {
            return $this->method_map[$method_name];
        }

        return $method_name;
    }

    /**
     * @param  string       $trait_name
     * @param  FileChecker  $file_checker
     * @return boolean
     */
    public static function traitExists($trait_name, FileChecker $file_checker)
    {
        if (isset(self::$trait_names[strtolower($trait_name)])) {
            return true;
        }

        if (isset(self::$existing_traits[strtolower($trait_name)])) {
            return self::$existing_traits[strtolower($trait_name)];
        }

        if ($file_checker->evaluateClassLike($trait_name, false) === false) {
            self::$existing_traits[strtolower($trait_name)] = false;
            return false;
        }

        self::$existing_traits[strtolower($trait_name)] = true;

        return true;
    }

    /**
     * @param  string  $trait_name
     * @return boolean
     */
    public static function hasCorrectCase($trait_name)
    {
        if (isset(self::$trait_names[strtolower($trait_name)])) {
            return self::$trait_names[strtolower($trait_name)] === $trait_name;
        }

        try {
            $reflection_trait = new \ReflectionClass($trait_name);
            return $reflection_trait->getName() === $trait_name;
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$trait_names = [];
        self::$existing_traits = [];
    }
}
