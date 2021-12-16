<?php

namespace Psalm\Internal\Type\Comparator;

/**
 * @psalm-immutable
 */
class TypeComparisonResult2
{
    /**
     * Result from a type comparison.
     *
     * @var bool
     */
    public $result;

    /**
     * True if the type being compared is less specific than the type it's being compared to.
     *
     * @var bool
     */
    public $is_less_specific_type;

    /**
     * Result from a type comparison after scalar type coercion has been performed.
     * For example, `int` is a subtype of `string` after scalar type coercion.
     *
     * @var bool
     */
    public $result_with_scalar_coercion;

    /**
     * Result from a type comparison after type coercion has been performed, not allowing for mixed to be coerced.
     * For example, for `class Bar extends Foo`, `Foo` is a subtype of `Bar` after type coercion.
     *
     * @var bool
     */
    public $result_with_coercion;

    /**
     * Result from a type comparison after type coercion has been performed, allowing mixed to be coerced to anything.
     *
     * @var bool
     */
    public $result_with_coercion_from_mixed;

    /**
     * Result from a type comparison if all scalar types are considered identical.
     *
     * TODO is this actually needed?
     *
     * @var bool
     */
    public $result_ignoring_scalar;

    /**
     * Result when allowing for `Stringable` objects to be cast to `string`.
     *
     * @var bool
     */
    public $result_with_to_string_cast;

    private function __construct(
        bool $result,
        ?bool $is_less_specific_type = null,
        ?bool $result_with_scalar_coercion = null,
        ?bool $result_with_coercion = null,
        ?bool $result_with_coercion_from_mixed = null,
        ?bool $result_ignoring_scalar = null,
        ?bool $result_with_to_string_cast = null
    ) {
        $this->result = $result;
        $this->is_less_specific_type = (!$result && $is_less_specific_type) ?? false;
        $this->result_with_scalar_coercion = ($result ?: $result_with_scalar_coercion) ?? false;
        $this->result_with_coercion =
            (($this->is_less_specific_type || $this->result_with_scalar_coercion) ?: $result_with_coercion) ?? false;
        $this->result_with_coercion_from_mixed =
            ($this->result_with_coercion ?: $result_with_coercion_from_mixed) ?? false;
        $this->result_ignoring_scalar = ($result ?: $result_ignoring_scalar) ?? false;
        $this->result_with_to_string_cast = ($result ?: $result_with_to_string_cast) ?? false;

        if ($this->is_less_specific_type && $result_with_coercion === false && $result_with_coercion_from_mixed) {
            // If coerced from mixed, don't set $this->result_with_coercion even though the type is less specific
            $this->result_with_coercion = false;
        }
    }

    /** @psalm-mutation-free */
    public static function true(bool $result = true): self
    {
        return new self($result);
    }

    /**
     * Note that this is distinct from `false` in that this returns a result that
     * has every value set to true except for `self::$result`. This is useful for
     * `and`ing when a result is known to not be true but coercion may be possible.
     *
     * @psalm-mutation-free
     */
    public static function notTrue(): self
    {
        return new self(false, true, true, true, true, true, true);
    }

    /** @psalm-mutation-free */
    public static function false(): self
    {
        return new self(false, false);
    }

    /** @psalm-mutation-free */
    public static function lessSpecific(bool $result = true): self
    {
        return new self(false, $result);
    }

    /** @psalm-mutation-free */
    public static function scalarCoerced(bool $result = true): self
    {
        return new self(false, false, $result);
    }

    /** @psalm-mutation-free */
    public static function coerced(): self
    {
        return new self(false, false, false, true);
    }

    /** @psalm-mutation-free */
    public static function coercedFromMixed(): self
    {
        return new self(false, true, false, false, true);
    }

    /** @psalm-mutation-free */
    // public static function ignoringScalar(): self
    // {
    //     return new self(false, false, false, false, false, true);
    // }

    /** @psalm-mutation-free */
    public static function requiresToStringCast(bool $result = true): self
    {
        return new self(false, false, false, false, false, false, $result);
    }

    public function and(self $other): self
    {
        return new self(
            $this->result && $other->result,
            $this->is_less_specific_type && $other->is_less_specific_type,
            $this->result_with_scalar_coercion && $other->result_with_scalar_coercion,
            $this->result_with_coercion && $other->result_with_coercion,
            $this->result_with_coercion_from_mixed && $other->result_with_coercion_from_mixed,
            $this->result_ignoring_scalar && $other->result_ignoring_scalar,
            $this->result_with_to_string_cast && $other->result_with_to_string_cast
        );
    }

    public function or(self $other): self
    {
        return new self(
            $this->result || $other->result,
            $this->is_less_specific_type || $other->is_less_specific_type,
            $this->result_with_scalar_coercion || $other->result_with_scalar_coercion,
            $this->result_with_coercion || $other->result_with_coercion,
            $this->result_with_coercion_from_mixed || $other->result_with_coercion_from_mixed,
            $this->result_ignoring_scalar || $other->result_ignoring_scalar,
            $this->result_with_to_string_cast || $other->result_with_to_string_cast
        );
    }

    public function not(): self
    {
        return new self(
            !$this->result,
            !$this->is_less_specific_type,
            !$this->result_with_scalar_coercion,
            !$this->result_with_coercion,
            !$this->result_with_coercion_from_mixed,
            !$this->result_ignoring_scalar,
            !$this->result_with_to_string_cast
        );
    }

    /**
     * Returns true if the result is already completely different, and further checks are not required.
     */
    public function completelyDifferent(): bool
    {
        return !$this->result
            && !$this->result_with_scalar_coercion
            && !$this->result_with_coercion
            && !$this->result_with_coercion_from_mixed
            && !$this->result_ignoring_scalar
            && !$this->result_with_to_string_cast
        ;
    }
}
