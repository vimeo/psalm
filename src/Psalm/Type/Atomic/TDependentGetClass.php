<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use function array_pop;
use function get_class;

/**
 * Represents a string whose value is a fully-qualified class found by get_class($var)
 */
class TDependentGetClass extends TString implements DependentType
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        TNonEmptyString::class => true,
        TNonFalsyString::class => true,
    ];

    protected const COERCIBLE_TO = parent::COERCIBLE_TO + [
        TLowercaseString::class => true,
        TNonEmptyLowercaseString::class => true,
        TSingleLetter::class => true,
    ];

    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @param string $typeof the variable id
     */
    public function __construct(string $typeof, Union $as_type)
    {
        $this->typeof = $typeof;
        $this->as_type = $as_type;
    }

    public function getId(bool $nested = false): string
    {
        return $this->as_type->isMixed()
            || $this->as_type->hasObject()
            ? 'class-string'
            : 'class-string<' . $this->as_type->getId() . '>';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'get-class-of<' . $this->typeof
            . (!$this->as_type->isMixed() && !$this->as_type->hasObject() ? ', ' . $this->as_type->getId() : '')
            . '>';
    }

    public function getVarId() : string
    {
        return $this->typeof;
    }

    public function getReplacement() : \Psalm\Type\Atomic
    {
        return new TClassString();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    protected function isSubtypeOf(
        Atomic $other,
        Codebase $codebase,
        bool $allow_interface_equality = false,
        bool $allow_int_to_float_coercion = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if ($other instanceof TDependentGetClass) {
            return UnionTypeComparator::isContainedBy(
                $codebase,
                $this->as_type,
                $other->as_type
            );
        }

        if ((get_class($other) === TClassString::class || get_class($other) === TLiteralClassString::class)
            && $this->as_type->isSingle()
        ) {
            $class_types = $this->as_type->getAtomicTypes();
            $class_type = array_pop($class_types);
            return $class_type->isSubtypeOf(
                $other->getConstraintType(),
                $codebase,
                $allow_interface_equality,
                $allow_int_to_float_coercion,
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
