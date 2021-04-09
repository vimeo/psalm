<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\GenericTypeComparator;
use Psalm\Internal\Type\Comparator\ObjectComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type;
use Psalm\Type\Atomic;
use function array_map;
use function get_class;
use function implode;
use function substr;

/**
 * Denotes an object type where the type of the object is known e.g. `Exception`, `Throwable`, `Foo\Bar`
 */
class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    protected const SUPERTYPES = parent::SUPERTYPES + [
        TObject::class => true,
    ];

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
    public function __construct(string $value, bool $was_static = false)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
        $this->was_static = $was_static;
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            return $this->value . '&' . implode('&', $this->extra_types);
        }

        return $this->value;
    }

    public function getId(bool $nested = false): string
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
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($this->value === 'static') {
            return 'static';
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format
        );

        return Type::getStringFromFQCLN(
            $this->value,
            $namespace,
            $aliased_classes,
            $this_class,
            true,
            $this->was_static
        ) . $intersection_types;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        if ($this->value === 'static') {
            return $php_major_version >= 8 ? 'static' : null;
        }

        if ($this->was_static) {
            return $php_major_version >= 8 ? 'static' : 'self';
        }

        return $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return ($this->value !== 'static' && $this->was_static === false) || $php_major_version >= 8;
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

    protected function isSubtypeOf(
        Atomic $other,
        Codebase $codebase,
        bool $allow_interface_equality = false,
        bool $allow_int_to_float_coercion = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if (get_class($other) === TGenericObject::class || get_class($other) === TIterable::class) {
            return GenericTypeComparator::isContainedBy(
                $codebase,
                $this,
                $other,
                $allow_interface_equality,
                $type_comparison_result
            );
        }

        if (get_class($other) === TNamedObject::class) {
            // TODO
            return ObjectComparator::isShallowlyContainedBy(
                $codebase,
                $this,
                $other,
                $allow_interface_equality,
                $type_comparison_result
            );
        }

        return parent::isSubtypeOf(
            $other,
            $codebase,
            $allow_interface_equality,
            $allow_int_to_float_coercion,
            $type_comparison_result
        );
    }
}
