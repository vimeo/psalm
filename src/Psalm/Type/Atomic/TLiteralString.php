<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type;
use Psalm\Type\Atomic;

use function addcslashes;
use function get_class;
use function is_numeric;
use function mb_strlen;
use function mb_substr;
use function strlen;
use function strtolower;

/**
 * Denotes a string whose value is known.
 */
class TLiteralString extends TString
{
    /** @var array<class-string<Scalar>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        TNonspecificLiteralString::class => true,
    ];

    /** @var array<class-string<Scalar>, true> */
    protected const COERCIBLE_TO = [ // Not extending parent since we can check literals for coercion
        TClassString::class => true,
        TLiteralClassString::class => true,
        TCallableString::class => true,
    ];

    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'string(' . $this->value . ')';
    }

    public function getId(bool $nested = false): string
    {
        // quote control characters, backslashes and double quote
        $no_newline_value = addcslashes($this->value, "\0..\37\\\"");
        if (mb_strlen($this->value) > 80) {
            return '"' . mb_substr($no_newline_value, 0, 80) . '...' . '"';
        }

        return '"' . $no_newline_value . '"';
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'string(' . $this->value . ')';
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
        return $use_phpdoc_format ? 'string' : "'" . $this->value . "'";
    }

    /**
     * @psalm-mutation-free
     */
    protected function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        switch (get_class($other)) {
            case TLiteralFloat::class:
            case TLiteralInt::class:
                return TypeComparisonResult2::scalarCoerced($this->value == $other->value);
            case self::class:
            case TLiteralClassString::class:
                return TypeComparisonResult2::true($this->value === $other->value);
            case TNonFalsyString::class:
                return TypeComparisonResult2::true($this->value !== '0');
            case TLowercaseString::class:
                return TypeComparisonResult2::true($this->value === strtolower($this->value));
            case TNonEmptyNonspecificLiteralString::class:
            case TNonEmptyString::class:
                return TypeComparisonResult2::true($this->value !== '');
            case TNonEmptyLowercaseString::class:
                return TypeComparisonResult2::true($this->value !== '' && $this->value === strtolower($this->value));
            case TSingleLetter::class:
                return TypeComparisonResult2::true(strlen($this->value) === 1);
            case TCallableString::class:
                // This commented out section was adapted from ScalarTypeComparator, but it doesn't actually do anything
                // because getCallableFromAtomic(callable-string) is guaranteed to return null. I'm leaving it for now
                // because I think _something_ should probably be done here, but I'm not quite sure what yet.

                // $this_callable = CallableTypeComparator::getCallableFromAtomic($codebase, $this);
                // $other_callable = CallableTypeComparator::getCallableFromAtomic($codebase, $other);

                // if ($this_callable && $other_callable) {
                //     if (!CallableTypeComparator::isContainedBy(
                //         $codebase,
                //         $this_callable,
                //         $other_callable,
                //         $type_comparison_result ?: new TypeComparisonResult()
                //     )) {
                //         return false;
                //     }
                // }

                return TypeComparisonResult2::true();
            case TNumericString::class:
                return TypeComparisonResult2::true(is_numeric($this->value));
        }

        return parent::containedByAtomic($other, $codebase);
    }
}
