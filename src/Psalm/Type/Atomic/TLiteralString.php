<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
// use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic;
use function get_class;
use function is_numeric;
use function mb_strlen;
use function mb_substr;
use function preg_replace;
use function strlen;
use function strtolower;

/**
 * Denotes a string whose value is known.
 */
class TLiteralString extends TString implements TILiteral
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true) : string
    {
        return 'string(' . $this->value . ')';
    }

    public function __toString(): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        $no_newline_value = preg_replace("/\n/m", '\n', $this->value);
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
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return $php_major_version >= 7 ? 'string' : null;
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
        return 'string';
    }

    protected function isSubtypeOf(
        Atomic $other,
        Codebase $codebase,
        bool $allow_interface_equality = false,
        bool $allow_int_to_float_coercion = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if (get_class($other) === TNonFalsyString::class) {
            return $this->value !== '0';
        }

        if (get_class($other) === TLiteralString::class) {
            return $this->value === $other->value;
        }

        if (get_class($other) === TLowercaseString::class) {
            return $this->value === strtolower($this->value);
        }

        if (get_class($other) === TNonEmptyString::class) {
            return $this->value !== '';
        }

        if (get_class($other) === TNonEmptyLowercaseString::class) {
            return $this->value !== '' && $this->value === strtolower($this->value);
        }

        if (get_class($other) === TSingleLetter::class) {
            return strlen($this->value) === 1;
        }

        if (get_class($other) === TCallableString::class) {
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

            return true;
        }

        if (get_class($other) === TNumericString::class || get_class($other) === TNumeric::class) {
            return is_numeric($this->value);
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
