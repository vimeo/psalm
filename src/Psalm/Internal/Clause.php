<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Storage\Assertion;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Stringable;

use function array_diff;
use function array_keys;
use function assert;
use function count;
use function hash;
use function implode;
use function ksort;
use function reset;
use function serialize;
use function substr;

/**
 * @internal
 * @psalm-immutable
 */
final class Clause implements Stringable
{
    use ImmutableNonCloneableTrait;

    public int $creating_object_id;

    /**
     * An array of strings of the form
     * [
     *     '$a' => ['falsy'],
     *     '$b' => ['!falsy'],
     *     '$c' => ['!null'],
     *     '$d' => ['string', 'int']
     * ]
     *
     * representing the formula
     *
     * !$a || $b || $c !== null || is_string($d) || is_int($d)
     *
     * @var array<string, non-empty-array<string, Assertion>>
     */
    public array $possibilities;

    /**
     * An array of things that are not true
     * [
     *     '$a' => ['!falsy'],
     *     '$b' => ['falsy'],
     *     '$c' => ['null'],
     *     '$d' => ['!string', '!int']
     * ]
     * represents the formula
     *
     * $a && !$b && $c === null && !is_string($d) && !is_int($d)
     *
     * @var array<string, non-empty-list<Assertion>>|null
     */
    public ?array $impossibilities = null;

    public bool $wedge;

    public bool $reconcilable;

    public string $hash;

    /**
     * @param array<string, non-empty-array<string, Assertion>>  $possibilities
     * @param array<string, bool> $redefined_vars
     */
    public function __construct(
        array $possibilities,
        public int $creating_conditional_id,
        int $creating_object_id,
        bool $wedge = false,
        bool $reconcilable = true,
        public bool $generated = false,
        public array $redefined_vars = [],
    ) {
        if ($wedge || !$reconcilable) {
            $this->hash = ($wedge ? 'w' : '') . $creating_object_id;
        } else {
            ksort($possibilities);

            $possibility_strings = [];

            foreach ($possibilities as $i => $v) {
                if (count($v) > 1) {
                    ksort($v);
                }
                $possibility_strings[$i] = array_keys($v);
            }

            /** @psalm-suppress ImpureFunctionCall */
            $data = serialize($possibility_strings);
            $this->hash = hash('xxh128', $data);
        }

        $this->possibilities = $possibilities;
        $this->wedge = $wedge;
        $this->reconcilable = $reconcilable;
        $this->creating_object_id = $creating_object_id;
    }

    public function contains(Clause $other_clause): bool
    {
        if (count($other_clause->possibilities) > count($this->possibilities)) {
            return false;
        }

        foreach ($other_clause->possibilities as $var => $_) {
            if (!isset($this->possibilities[$var])) {
                return false;
            }
        }

        foreach ($other_clause->possibilities as $var => $possible_types) {
            if (count(array_diff($possible_types, $this->possibilities[$var]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        $clause_strings = [];

        foreach ($this->possibilities as $var_id => $values) {
            if ($var_id[0] === '*') {
                $var_id = '<expr>';
            }

            $var_id_clauses = [];
            foreach ($values as $value) {
                $value = (string) $value;
                if ($value === 'falsy') {
                    $var_id_clauses[] = '!'.$var_id;
                    continue;
                }

                if ($value === '!falsy') {
                    $var_id_clauses[] = $var_id;
                    continue;
                }

                $negate = false;

                if ($value[0] === '!') {
                    $negate = true;
                    $value  = substr($value, 1);
                }

                if ($value[0] === '=') {
                    $value = substr($value, 1);
                }

                if ($negate) {
                    $var_id_clauses[] = $var_id.' is not '.$value;
                    continue;
                }

                $var_id_clauses[] = $var_id.' is '.$value;
            }

            if (count($var_id_clauses) > 1) {
                $clause_strings[] = '('.implode(') || (', $var_id_clauses).')';
            } else {
                assert(!empty($var_id_clauses));
                $clause_strings[] = reset($var_id_clauses);
            }
        }

        if (count($clause_strings) > 1) {
            return '(' . implode(') || (', $clause_strings) . ')';
        }

        assert(!empty($clause_strings));

        return reset($clause_strings);
    }

    public function removePossibilities(string $var_id): ?self
    {
        $possibilities = $this->possibilities;
        unset($possibilities[$var_id]);

        if (!$possibilities) {
            return null;
        }

        return new self(
            $possibilities,
            $this->creating_conditional_id,
            $this->creating_object_id,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars,
        );
    }

    /**
     * @param non-empty-array<string, Assertion> $clause_var_possibilities
     */
    public function addPossibilities(string $var_id, array $clause_var_possibilities): self
    {
        $possibilities = $this->possibilities;
        $possibilities[$var_id] = $clause_var_possibilities;

        return new self(
            $possibilities,
            $this->creating_conditional_id,
            $this->creating_object_id,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars,
        );
    }

    public function calculateNegation(): self
    {
        if ($this->impossibilities !== null) {
            return $this;
        }

        $impossibilities = [];

        foreach ($this->possibilities as $var_id => $possibility) {
            $impossibility = [];

            foreach ($possibility as $type) {
                if (!$type->hasEquality()
                    || (($inner_type = $type->getAtomicType())
                        && ($inner_type instanceof TLiteralInt
                            || $inner_type instanceof TLiteralFloat
                            || $inner_type instanceof TLiteralString
                            || $inner_type instanceof TClassConstant
                            || $inner_type instanceof TEnumCase))
                ) {
                    $impossibility[] = $type->getNegation();
                }
            }

            if ($impossibility) {
                $impossibilities[$var_id] = $impossibility;
            }
        }

        $clause = clone $this;

        $clause->impossibilities = $impossibilities;

        return $clause;
    }
}
