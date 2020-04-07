<?php
namespace Psalm\Type\Atomic;

use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Type\TemplateResult;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;
use function substr;
use function array_map;
use function strtolower;

class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * @var bool
     */
    public $was_static = false;

    /**
     * @param string $value the name of the object
     */
    public function __construct($value, bool $was_static = false)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
        $this->was_static = $was_static;
    }

    public function __toString()
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        if ($include_extra && $this->extra_types) {
            return $this->value . '&' . implode('&', $this->extra_types);
        }

        return $this->value;
    }

    public function getId(bool $nested = false)
    {
        if ($this->extra_types) {
            return $this->value . '&' . implode(
                '&',
                array_map(
                    function ($type) {
                        return $type->getId(true);
                    },
                    $this->extra_types
                )
            );
        }

        return $this->was_static ? $this->value . '&static' : $this->value;
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
        if ($this->value === 'static') {
            return 'static';
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format
        );

        return Type::getStringFromFQCLN($this->value, $namespace, $aliased_classes, $this_class, true)
            . $intersection_types;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string, string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string|null
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        if ($this->value === 'static') {
            return null;
        }

        return $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }

    public function canBeFullyExpressedInPhp()
    {
        return $this->value !== 'static';
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
    }

    public function getChildNodes() : array
    {
        return $this->extra_types !== null ? $this->extra_types : [];
    }
}
