<?php

namespace Psalm\Internal;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

use function array_diff;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function json_encode;
use function ksort;
use function md5;
use function reset;
use function sort;
use function substr;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 *
 * @psalm-immutable
 */
class Clause
{
    /** @var int */
    public $creating_conditional_id;

    /** @var int */
    public $creating_object_id;

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
    public $possibilities;

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
    public $impossibilities;

    /** @var bool */
    public $wedge;

    /** @var bool */
    public $reconcilable;

    /** @var bool */
    public $generated = false;

    /** @var array<string, bool> */
    public $redefined_vars = [];

    /** @var string|int */
    public $hash;

    /**
     * @param array<string, non-empty-array<string, Assertion>>  $possibilities
     * @param array<string, bool> $redefined_vars
     */
    public function __construct(
        array $possibilities,
        int $creating_conditional_id,
        int $creating_object_id,
        bool $wedge = false,
        bool $reconcilable = true,
        bool $generated = false,
        array $redefined_vars = []
    ) {
        if ($wedge || !$reconcilable) {
            $this->hash = ($wedge ? 'w' : '') . $creating_object_id;
        } else {
            ksort($possibilities);

            $possibility_strings = [];

            foreach ($possibilities as $i => $_) {
                krsort($possibilities[$i]);
                $possibility_strings[$i] = array_keys($possibilities[$i]);
            }

            $this->hash = md5(json_encode($possibility_strings, JSON_THROW_ON_ERROR));
        }

        $this->possibilities = $possibilities;
        $this->wedge = $wedge;
        $this->reconcilable = $reconcilable;
        $this->generated = $generated;
        $this->redefined_vars = $redefined_vars;
        $this->creating_conditional_id = $creating_conditional_id;
        $this->creating_object_id = $creating_object_id;
    }

    public function contains(Clause $other_clause): bool
    {
        if (count($other_clause->possibilities) > count($this->possibilities)) {
            return false;
        }

        foreach ($other_clause->possibilities as $var => $possible_types) {
            if (!isset($this->possibilities[$var]) || count(array_diff($possible_types, $this->possibilities[$var]))) {
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
        $clause_strings = array_map(
            /**
             * @param non-empty-array<string, Assertion> $values
             */
            function (string $var_id, array $values): string {
                if ($var_id[0] === '*') {
                    $var_id = '<expr>';
                }

                $var_id_clauses = array_map(
                    function (Assertion $value) use ($var_id): string {
                        $value = (string) $value;
                        if ($value === 'falsy') {
                            return '!' . $var_id;
                        }

                        if ($value === '!falsy') {
                            return $var_id;
                        }

                        $negate = false;

                        if ($value[0] === '!') {
                            $negate = true;
                            $value = substr($value, 1);
                        }

                        if ($value[0] === '=') {
                            $value = substr($value, 1);
                        }

                        if ($negate) {
                            return $var_id . ' is not ' . $value;
                        }

                        return $var_id . ' is ' . $value;
                    },
                    $values
                );

                if (count($var_id_clauses) > 1) {
                    return '(' . implode(') || (', $var_id_clauses) . ')';
                }

                return reset($var_id_clauses);
            },
            array_keys($this->possibilities),
            array_values($this->possibilities)
        );

        if (count($clause_strings) > 1) {
            return '(' . implode(') || (', $clause_strings) . ')';
        }

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
            $this->redefined_vars
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
            $this->redefined_vars
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
