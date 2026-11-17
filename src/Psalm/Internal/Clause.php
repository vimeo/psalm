<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsEqualIsset;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsLooselyEqual;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotLooselyEqual;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\NotInArray;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Stringable;

use function array_diff_key;
use function array_keys;
use function assert;
use function count;
use function hash;
use function implode;
use function ksort;
use function preg_match;
use function preg_quote;
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
     * Every assertion is keyed by its string form, which is how clauses are compared
     * (see {@see Clause::contains()} and {@see Algebra::simplifyCNF()}).
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

    /**
     * Whether some possibilities couldn't be negated, and are missing from the impossibilities
     */
    public bool $partial_negation = false;

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
        if ($wedge) {
            $this->hash = 'w' . $creating_object_id;
        } else {
            ksort($possibilities);

            $possibility_strings = [];

            foreach ($possibilities as $i => $v) {
                if (count($v) > 1) {
                    ksort($v);
                }
                $possibility_strings[$i] = array_keys($v);
            }

            $data = serialize($possibility_strings);

            // a clause that is not reconcilable is not the same as one with the same possibilities from another
            // condition: it also holds what the condition says about its other disjuncts
            $this->hash = ($reconcilable ? '' : 'u' . $creating_object_id . ':') . hash('xxh128', $data);
        }

        $this->possibilities = $possibilities;
        $this->wedge = $wedge;
        $this->reconcilable = $reconcilable;
        $this->creating_object_id = $creating_object_id;
    }

    /**
     * @psalm-mutation-free
     */
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
            if (array_diff_key($possible_types, $this->possibilities[$var])) {
                return false;
            }
        }

        return true;
    }

    /**
     * The string of the assertion without what only tells comparisons apart: the operand or array it was compared
     * with, and the whole of a long literal string
     *
     * @psalm-mutation-free
     */
    public static function getAssertionDisplayString(Assertion $assertion): string
    {
        if (($assertion instanceof IsIdentical
                || $assertion instanceof IsNotIdentical
                || $assertion instanceof IsLooselyEqual
                || $assertion instanceof IsNotLooselyEqual)
            && $assertion->comparand !== null
        ) {
            return (string) new ($assertion::class)($assertion->type);
        }

        if (($assertion instanceof InArray || $assertion instanceof NotInArray) && $assertion->haystack !== null) {
            return (string) new ($assertion::class)($assertion->type);
        }

        if ($assertion instanceof IsType) {
            return $assertion->type->getId();
        }

        if ($assertion instanceof IsNotType) {
            return '!' . $assertion->type->getId();
        }

        return (string) $assertion;
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function __toString(): string
    {
        $clause_strings = [];

        foreach ($this->possibilities as $var_id => $values) {
            if ($var_id[0] === '*') {
                $var_id = '<expr>';
            }

            $var_id_clauses = [];
            foreach ($values as $value) {
                $value = self::getAssertionDisplayString($value);
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

    /**
     * What is assigned to an offset or a property of a mixed variable is not tracked, so a clause on one says nothing
     * of it. Where the clause only holds if the variable is set, it says that instead.
     *
     * @param array<string> $mixed_var_ids
     */
    public function forgetItemsOfMixedVars(array $mixed_var_ids, int $cond_object_id): self
    {
        $possibilities = [];

        foreach ($this->possibilities as $key => $assertions) {
            foreach ($mixed_var_ids as $mixed_var_id) {
                if (!preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                    continue;
                }

                foreach ($assertions as $assertion) {
                    if (!$assertion instanceof IsIsset
                        && !$assertion instanceof IsEqualIsset
                        && !$assertion instanceof NonEmpty
                    ) {
                        return new self([], $cond_object_id, $cond_object_id, true);
                    }
                }

                $possibilities[$mixed_var_id] = ['=isset' => new IsEqualIsset()];

                continue 2;
            }

            $possibilities[$key] = $assertions;
        }

        if ($possibilities === $this->possibilities) {
            return $this;
        }

        // not written in the condition, so not redundant with what is already known
        return new self(
            $possibilities,
            $this->creating_conditional_id,
            $this->creating_object_id,
            $this->wedge,
            $this->reconcilable,
            true,
            $this->redefined_vars,
        );
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
        $partial_negation = false;

        foreach ($this->possibilities as $var_id => $possibility) {
            $impossibility = [];

            foreach ($possibility as $type) {
                if (!$type->hasEquality()
                    || (($inner_type = $type->getAtomicType())
                        && ($inner_type instanceof TLiteralInt
                            || $inner_type instanceof TLiteralFloat
                            || $inner_type instanceof TLiteralString
                            || $inner_type instanceof TClassConstant
                            || $inner_type instanceof TEnumCase
                            || ($inner_type instanceof TNull
                                && ($type instanceof IsLooselyEqual || $type instanceof IsNotLooselyEqual))
                            || ($inner_type instanceof TNamedObject && $inner_type->is_static)))
                ) {
                    $impossibility[] = $type->getNegation();
                } else {
                    $partial_negation = true;
                }
            }

            if ($impossibility) {
                $impossibilities[$var_id] = $impossibility;
            }
        }

        $clause = clone $this;

        $clause->impossibilities = $impossibilities;
        $clause->partial_negation = $partial_negation;

        return $clause;
    }
}
