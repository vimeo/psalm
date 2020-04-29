<?php
namespace Psalm\Type\Atomic;

use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Type\TemplateResult;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Union;
use Psalm\Storage\MethodStorage;
use function array_map;
use function strtolower;

class TConditional extends \Psalm\Type\Atomic
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $defining_class;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @var Union
     */
    public $conditional_type;

    /**
     * @var Union
     */
    public $if_type;

    /**
     * @var Union
     */
    public $else_type;

    /**
     * @param string $defining_class
     */
    public function __construct(
        string $param_name,
        string $defining_class,
        Union $as_type,
        Union $conditional_type,
        Union $if_type,
        Union $else_type
    ) {
        $this->param_name = $param_name;
        $this->defining_class = $defining_class;
        $this->as_type = $as_type;
        $this->conditional_type = $conditional_type;
        $this->if_type = $if_type;
        $this->else_type = $else_type;
    }

    public function __toString()
    {
        return '('
            . $this->param_name
            . ' is ' . $this->conditional_type
            . ' ? ' . $this->if_type
            . ' : ' . $this->else_type
            . ')';
    }

    public function __clone()
    {
        $this->conditional_type = clone $this->conditional_type;
        $this->if_type = clone $this->if_type;
        $this->else_type = clone $this->else_type;
        $this->as_type = clone $this->as_type;
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return '';
    }

    public function getId(bool $nested = false)
    {
        return '('
            . $this->param_name . ':' . $this->defining_class
            . ' is ' . $this->conditional_type->getId()
            . ' ? ' . $this->if_type->getId()
            . ' : ' . $this->else_type->getId()
            . ')';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return null;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string, string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) {
        return '';
    }

    public function getChildNodes() : array
    {
        return [$this->conditional_type, $this->if_type, $this->else_type];
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        $this->conditional_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);
    }
}
