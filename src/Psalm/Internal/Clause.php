<?php
namespace Psalm\Internal;

use function array_diff;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function json_encode;
use function ksort;
use function md5;
use function sort;
use function mt_rand;
use function array_unique;
use function strpos;

/**
 * @internal
 *
 * @psalm-immutable
 */
class Clause
{
    /** @var ?int */
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
     * @var array<string, non-empty-list<string>>
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
     * @var array<string, non-empty-list<string>>|null
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

    /** @var string */
    public $hash;

    /**
     * @param array<string, non-empty-list<string>>  $possibilities
     * @param bool                          $wedge
     * @param bool                          $reconcilable
     * @param bool                          $generated
     * @param array<string, bool> $redefined_vars
     */
    public function __construct(
        array $possibilities,
        $wedge = false,
        $reconcilable = true,
        $generated = false,
        array $redefined_vars = [],
        ?int $creating_object_id = null
    ) {
        $this->possibilities = $possibilities;
        $this->wedge = $wedge;
        $this->reconcilable = $reconcilable;
        $this->generated = $generated;
        $this->redefined_vars = $redefined_vars;
        $this->creating_object_id = $creating_object_id;

        if ($wedge || !$reconcilable) {
            /** @psalm-suppress ImpureFunctionCall as this has to be globally unique */
            $this->hash = (string) mt_rand(0, 1000000);
        } else {
            ksort($possibilities);

            foreach ($possibilities as &$possible_types) {
                sort($possible_types);
            }

            $this->hash = md5((string) json_encode($possibilities));
        }
    }

    /**
     * @param  Clause $other_clause
     *
     * @return bool
     */
    public function contains(Clause $other_clause)
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

    public function __toString()
    {
        return implode(
            ' || ',
            array_map(
                /**
                 * @param string $var_id
                 * @param string[] $values
                 *
                 * @return string
                 */
                function ($var_id, $values) {
                    return implode(
                        ' || ',
                        array_map(
                            /**
                             * @param string $value
                             *
                             * @return string
                             */
                            function ($value) use ($var_id) {
                                if ($value === 'falsy') {
                                    return '!' . $var_id;
                                }

                                if ($value === '!falsy') {
                                    return $var_id;
                                }

                                return $var_id . '==' . $value;
                            },
                            $values
                        )
                    );
                },
                array_keys($this->possibilities),
                array_values($this->possibilities)
            )
        );
    }

    public function makeUnique() : self
    {
        $possibilities = $this->possibilities;

        foreach ($possibilities as $var_id => $var_possibilities) {
            $possibilities[$var_id] = array_values(array_unique($var_possibilities));
        }

        return new self(
            $possibilities,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars,
            $this->creating_object_id
        );
    }

    public function removePossibilities(string $var_id) : self
    {
        $possibilities = $this->possibilities;
        unset($possibilities[$var_id]);

        return new self(
            $possibilities,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars,
            $this->creating_object_id
        );
    }

    /**
     * @param non-empty-list<string> $clause_var_possibilities
     */
    public function addPossibilities(string $var_id, array $clause_var_possibilities) : self
    {
        $possibilities = $this->possibilities;
        $possibilities[$var_id] = $clause_var_possibilities;

        return new self(
            $possibilities,
            $this->wedge,
            $this->reconcilable,
            $this->generated,
            $this->redefined_vars,
            $this->creating_object_id
        );
    }

    public function calculateNegation() : self
    {
        if ($this->impossibilities !== null) {
            return $this;
        }

        $impossibilities = [];

        foreach ($this->possibilities as $var_id => $possibility) {
            $impossibility = [];

            foreach ($possibility as $type) {
                if (($type[0] !== '=' && $type[0] !== '~'
                        && (!isset($type[1]) || ($type[1] !== '=' && $type[1] !== '~')))
                    || strpos($type, '(')
                    || strpos($type, 'getclass-')
                ) {
                    $impossibility[] = \Psalm\Type\Algebra::negateType($type);
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
