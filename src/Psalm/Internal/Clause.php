<?php
namespace Psalm\Internal;

/**
 * @internal
 */
class Clause
{
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
     * @var array<string, array<string>>
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
     * @var array<string, array<string>>|null
     */
    public $impossibilities;

    /** @var bool */
    public $wedge;

    /** @var bool */
    public $reconcilable;

    /** @var bool */
    public $generated = false;

    /**
     * @param array<string, array<string>>  $possibilities
     * @param bool                          $wedge
     * @param bool                          $reconcilable
     * @param bool                          $generated
     */
    public function __construct(array $possibilities, $wedge = false, $reconcilable = true, $generated = false)
    {
        $this->possibilities = $possibilities;
        $this->wedge = $wedge;
        $this->reconcilable = $reconcilable;
        $this->generated = $generated;
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

    /**
     * Gets a hash of the object – will be unique if we're unable to easily reconcile this with others
     *
     * @return string
     */
    public function getHash()
    {
        ksort($this->possibilities);

        foreach ($this->possibilities as &$possible_types) {
            sort($possible_types);
        }

        $possibility_string = json_encode($this->possibilities);
        if (!$possibility_string) {
            return (string)rand(0, 10000000);
        }

        return md5($possibility_string) .
            ($this->wedge || !$this->reconcilable ? spl_object_hash($this) : '');
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
}
