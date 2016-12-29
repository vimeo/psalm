<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Context;

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
     * @var array<MethodChecker>
     */
    protected $methods = [];

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

        $this->class = $class;
        $this->namespace = $source->getNamespace();
        $this->aliased_classes = $source->getAliasedClasses();
        $this->file_name = $source->getFileName();
        $this->file_path = $source->getFilePath();
        $this->fq_class_name = $fq_class_name;

        $this->parent_class = null;

        $this->suppressed_issues = $source->getSuppressedIssues();

        self::$trait_names[strtolower($this->fq_class_name)] = $this->fq_class_name;

        self::$class_checkers[$fq_class_name] = $this;
    }

    /**
     * @param   bool            $check_methods
     * @param   Context|null    $class_context
     * @param   bool            $update_docblocks
     * @return void
     */
    public function check($check_methods = true, Context $class_context = null, $update_docblocks = false)
    {
        if (!$class_context) {
            throw new \InvalidArgumentException('TraitChecker::check must be called with a $class_context');
        }

        parent::check(false, $class_context);
    }

    /**
     * @param   Context    $class_context
     * @return  void
     */
    public function checkMethods(Context $class_context)
    {
        foreach ($this->methods as $method_checker) {
            $method_checker->check($class_context, null);
        }
    }

    /**
     * @param   array $method_map
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
     * @param  string $trait_name
     * @return boolean
     */
    public static function traitExists($trait_name)
    {
        if (isset(self::$trait_names[strtolower($trait_name)])) {
            return true;
        }

        if (isset(self::$existing_traits[strtolower($trait_name)])) {
            return self::$existing_traits[strtolower($trait_name)];
        }

        $trait_exists = trait_exists($trait_name);

        self::$existing_traits[strtolower($trait_name)] = $trait_exists;

        return $trait_exists;
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
