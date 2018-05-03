<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\Expression\AssertionFinder;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;

class AlgebraChecker
{
    /** @var array<string, array<int, string>> */
    private static $broken_paths = [];

    /**
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string|null              $this_class_name
     * @param  FileSource         $source
     *
     * @return array<int, Clause>
     */
    public static function getFormula(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        FileSource $source
    ) {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_assertions = self::getFormula(
                $conditional->left,
                $this_class_name,
                $source
            );

            $right_assertions = self::getFormula(
                $conditional->right,
                $this_class_name,
                $source
            );

            return array_merge(
                $left_assertions,
                $right_assertions
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            // at the moment we only support formulae in CNF

            if (!$conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd &&
                !$conditional->left instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            ) {
                $left_clauses = self::getFormula(
                    $conditional->left,
                    $this_class_name,
                    $source
                );
            } else {
                $left_clauses = [new Clause([], true)];
            }

            if (!$conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd &&
                !$conditional->right instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            ) {
                $right_clauses = self::getFormula(
                    $conditional->right,
                    $this_class_name,
                    $source
                );
            } else {
                $right_clauses = [new Clause([], true)];
            }

            return self::combineOredClauses($left_clauses, $right_clauses);
        }

        $assertions = AssertionFinder::getAssertions(
            $conditional,
            $this_class_name,
            $source
        );

        if ($assertions) {
            $clauses = [];

            foreach ($assertions as $var => $type) {
                if ($type === 'isset' || $type === '!empty') {
                    $key_parts = self::breakUpPathIntoParts($var);

                    $base_key = array_shift($key_parts);

                    if ($type === 'isset') {
                        $clauses[] = new Clause([$base_key => ['^isset']]);
                    } else {
                        $clauses[] = new Clause([$base_key => ['^!empty']]);
                    }

                    if (!empty($key_parts)) {
                        $clauses[] = new Clause([$base_key => ['!false']]);
                        $clauses[] = new Clause([$base_key => ['!int']]);
                    }

                    while ($key_parts) {
                        $divider = array_shift($key_parts);

                        if ($divider === '[') {
                            $array_key = array_shift($key_parts);
                            array_shift($key_parts);

                            $new_base_key = $base_key . '[' . $array_key . ']';

                            $base_key = $new_base_key;
                        } elseif ($divider === '->') {
                            $property_name = array_shift($key_parts);
                            $new_base_key = $base_key . '->' . $property_name;

                            $base_key = $new_base_key;
                        } else {
                            throw new \InvalidArgumentException('Unexpected divider ' . $divider);
                        }

                        if ($type === 'isset') {
                            $clauses[] = new Clause([$base_key => ['^isset']]);
                        } else {
                            $clauses[] = new Clause([$base_key => ['^!empty']]);
                        }

                        if (count($key_parts)) {
                            $clauses[] = new Clause([$base_key => ['!false']]);
                            $clauses[] = new Clause([$base_key => ['!int']]);
                        }
                    }
                } else {
                    $clauses[] = new Clause([$var => [$type]]);
                }
            }

            return $clauses;
        }

        return [new Clause([], true)];
    }

    /**
     * @param  string $path
     *
     * @return array<int, string>
     */
    public static function breakUpPathIntoParts($path)
    {
        if (isset(self::$broken_paths[$path])) {
            return self::$broken_paths[$path];
        }

        $chars = str_split($path);

        $string_char = null;
        $escape_char = false;

        $parts = [''];
        $parts_offset = 0;

        for ($i = 0, $char_count = count($chars); $i < $char_count; ++$i) {
            $char = $chars[$i];

            if ($string_char) {
                if ($char === $string_char && !$escape_char) {
                    $string_char = null;
                }

                if ($char === '\\') {
                    $escape_char = !$escape_char;
                }

                $parts[$parts_offset] .= $char;
                continue;
            }

            switch ($char) {
                case '[':
                case ']':
                    $parts_offset++;
                    $parts[$parts_offset] = $char;
                    $parts_offset++;
                    continue;

                case '\'':
                case '"':
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
                    $string_char = $char;

                    continue;

                case '-':
                    if ($i < $char_count - 1 && $chars[$i + 1] === '>') {
                        ++$i;

                        $parts_offset++;
                        $parts[$parts_offset] = '->';
                        $parts_offset++;
                        continue;
                    }
                    // fall through

                default:
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
            }
        }

        self::$broken_paths[$path] = $parts;

        return $parts;
    }

    /**
     * Negates a set of clauses
     * negateClauses([$a || $b]) => !$a && !$b
     * negateClauses([$a, $b]) => !$a || !$b
     * negateClauses([$a, $b || $c]) =>
     *   (!$a || !$b) &&
     *   (!$a || !$c)
     * negateClauses([$a, $b || $c, $d || $e || $f]) =>
     *   (!$a || !$b || !$d) &&
     *   (!$a || !$b || !$e) &&
     *   (!$a || !$b || !$f) &&
     *   (!$a || !$c || !$d) &&
     *   (!$a || !$c || !$e) &&
     *   (!$a || !$c || !$f)
     *
     * @param  array<int, Clause>  $clauses
     *
     * @return array<int, Clause>
     */
    public static function negateFormula(array $clauses)
    {
        foreach ($clauses as $clause) {
            self::calculateNegation($clause);
        }

        return self::groupImpossibilities($clauses);
    }

    /**
     * @param  Clause $clause
     *
     * @return void
     */
    public static function calculateNegation(Clause $clause)
    {
        if ($clause->impossibilities !== null) {
            return;
        }

        $impossibilities = [];

        foreach ($clause->possibilities as $var_id => $possiblity) {
            $impossibility = [];

            foreach ($possiblity as $type) {
                if ($type[0] !== '^' && (!isset($type[1]) || $type[1] !== '^')) {
                    $impossibility[] = TypeChecker::negateType($type);
                }
            }

            if ($impossibility) {
                $impossibilities[$var_id] = $impossibility;
            }
        }

        $clause->impossibilities = $impossibilities;
    }

    /**
     * This looks to see if there are any clauses in one formula that contradict
     * clauses in another formula, or clauses that duplicate previous clauses
     *
     * e.g.
     * if ($a) { }
     * elseif ($a) { }
     *
     * @param  array<int, Clause>   $formula1
     * @param  array<int, Clause>   $formula2
     * @param  StatementsChecker    $statements_checker,
     * @param  PhpParser\Node       $stmt
     * @param  array<string, bool>  $new_assigned_var_ids
     *
     * @return void
     */
    public static function checkForParadox(
        array $formula1,
        array $formula2,
        StatementsChecker $statements_checker,
        PhpParser\Node $stmt,
        array $new_assigned_var_ids
    ) {
        $negated_formula2 = self::negateFormula($formula2);

        // remove impossible types
        foreach ($negated_formula2 as $clause_a) {
            if (count($negated_formula2) === 1) {
                foreach ($clause_a->possibilities as $key => $values) {
                    if (count($values) > 1
                        && count(array_unique($values)) < count($values)
                        && !isset($new_assigned_var_ids[$key])
                    ) {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                'Found a redundant condition when evaluating ' . $key,
                                new CodeLocation($statements_checker, $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }

            if (!$clause_a->reconcilable || $clause_a->wedge) {
                continue;
            }

            foreach ($formula1 as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                $clause_a_contains_b_possibilities = true;

                foreach ($clause_b->possibilities as $key => $keyed_possibilities) {
                    if (!isset($clause_a->possibilities[$key])) {
                        $clause_a_contains_b_possibilities = false;
                        break;
                    }

                    if ($clause_a->possibilities[$key] != $keyed_possibilities) {
                        $clause_a_contains_b_possibilities = false;
                        break;
                    }
                }

                if ($clause_a_contains_b_possibilities) {
                    if (IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Encountered a paradox when evaluating the conditional',
                            new CodeLocation($statements_checker, $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }
            }
        }
    }

    /**
     * This is a very simple simplification heuristic
     * for CNF formulae.
     *
     * It simplifies formulae:
     *     ($a) && ($a || $b) => $a
     *     (!$a) && (!$b) && ($a || $b || $c) => $c
     *
     * @param  array<int, Clause>  $clauses
     *
     * @return array<int, Clause>
     */
    public static function simplifyCNF(array $clauses)
    {
        $cloned_clauses = [];

        // avoid strict duplicates
        foreach ($clauses as $clause) {
            $cloned_clauses[$clause->getHash()] = clone $clause;
        }

        // remove impossible types
        foreach ($cloned_clauses as $clause_a) {
            if (count($clause_a->possibilities) !== 1 || count(array_values($clause_a->possibilities)[0]) !== 1) {
                continue;
            }

            if (!$clause_a->reconcilable || $clause_a->wedge) {
                continue;
            }

            $clause_var = array_keys($clause_a->possibilities)[0];
            $only_type = array_pop(array_values($clause_a->possibilities)[0]);
            $negated_clause_type = TypeChecker::negateType($only_type);

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                if (isset($clause_b->possibilities[$clause_var]) &&
                    in_array($negated_clause_type, $clause_b->possibilities[$clause_var], true)
                ) {
                    $clause_b->possibilities[$clause_var] = array_filter(
                        $clause_b->possibilities[$clause_var],
                        /**
                         * @param string $possible_type
                         *
                         * @return bool
                         */
                        function ($possible_type) use ($negated_clause_type) {
                            return $possible_type !== $negated_clause_type;
                        }
                    );

                    if (count($clause_b->possibilities[$clause_var]) === 0) {
                        unset($clause_b->possibilities[$clause_var]);
                    }
                }
            }
        }

        $cloned_clauses = array_filter(
            $cloned_clauses,
            /**
             * @return bool
             */
            function (Clause $clause) {
                return (bool)count($clause->possibilities);
            }
        );

        $simplified_clauses = [];

        foreach ($cloned_clauses as $clause_a) {
            $is_redundant = false;

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                if ($clause_a->contains($clause_b)) {
                    $is_redundant = true;
                    break;
                }
            }

            if (!$is_redundant) {
                $simplified_clauses[] = $clause_a;
            }
        }

        return $simplified_clauses;
    }

    /**
     * Look for clauses with only one possible value
     *
     * @param  array<int, Clause>  $clauses
     *
     * @return array<string, string>
     */
    public static function getTruthsFromFormula(array $clauses)
    {
        $truths = [];

        if (empty($clauses)) {
            return [];
        }

        foreach ($clauses as $clause) {
            if (!$clause->reconcilable) {
                continue;
            }

            foreach ($clause->possibilities as $var => $possible_types) {
                // if there's only one possible type, return it
                if (count($clause->possibilities) === 1 && count($possible_types) === 1) {
                    if (isset($truths[$var])) {
                        $truths[$var] .= '&' . array_pop($possible_types);
                    } else {
                        $truths[$var] = array_pop($possible_types);
                    }
                } elseif (count($clause->possibilities) === 1) {
                    $with_brackets = 0;

                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = array_filter(
                        $possible_types,
                        /**
                         * @param  string $possible_type
                         *
                         * @return bool
                         *
                         * @psalm-suppress MixedOperand
                         */
                        function ($possible_type) use (&$with_brackets) {
                            $with_brackets += (int) (strpos($possible_type, '(') > 0);
                            return $possible_type[0] !== '!';
                        }
                    );

                    if ($things_that_can_be_said && count($things_that_can_be_said) === count($possible_types)) {
                        $things_that_can_be_said = array_unique($things_that_can_be_said);

                        if ($with_brackets > 1) {
                            $bracket_groups = [];

                            $removed = 0;

                            foreach ($things_that_can_be_said as $i => $t) {
                                if (preg_match('/^\^(int|string|float)\(/', $t, $matches)) {
                                    $options = substr($t, strlen((string) $matches[0]), -1);

                                    if (!isset($bracket_groups[(string) $matches[1]])) {
                                        $bracket_groups[(string) $matches[1]] = $options;
                                    } else {
                                        $bracket_groups[(string) $matches[1]] .= ',' . $options;
                                    }

                                    array_splice($things_that_can_be_said, $i - $removed, 1);
                                    $removed++;
                                }
                            }

                            foreach ($bracket_groups as $type => $options) {
                                $things_that_can_be_said[] = '^' . $type . '(' . $options . ')';
                            }
                        }

                        $truths[$var] = implode('|', $things_that_can_be_said);
                    }
                }
            }
        }

        return $truths;
    }

    /**
     * @param  array<int, Clause>  $clauses
     *
     * @return array<int, Clause>
     */
    protected static function groupImpossibilities(array $clauses)
    {
        $clause = array_pop($clauses);

        $new_clauses = [];

        if (!empty($clauses)) {
            $grouped_clauses = self::groupImpossibilities($clauses);

            foreach ($grouped_clauses as $grouped_clause) {
                if ($clause->impossibilities === null) {
                    throw new \UnexpectedValueException('$clause->impossibilities should not be null');
                }

                foreach ($clause->impossibilities as $var => $impossible_types) {
                    foreach ($impossible_types as $impossible_type) {
                        $new_clause_possibilities = $grouped_clause->possibilities;

                        if (isset($grouped_clause->possibilities[$var])) {
                            $new_clause_possibilities[$var][] = $impossible_type;
                        } else {
                            $new_clause_possibilities[$var] = [$impossible_type];
                        }

                        $new_clause = new Clause($new_clause_possibilities);

                        //$new_clause->reconcilable = $clause->reconcilable;

                        $new_clauses[] = $new_clause;
                    }
                }
            }
        } elseif ($clause && !$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new \UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $new_clause = new Clause([$var => [$impossible_type]]);
                    //$new_clause->reconcilable = $clause->reconcilable;
                    $new_clauses[] = $new_clause;
                }
            }
        }

        return $new_clauses;
    }

    /**
     * @param  array<int, Clause>  $left_clauses
     * @param  array<int, Clause>  $right_clauses
     *
     * @return array<int, Clause>
     */
    public static function combineOredClauses(array $left_clauses, array $right_clauses)
    {
        // we cannot deal, at the moment, with orring non-CNF clauses
        if (count($left_clauses) !== 1 || count($right_clauses) !== 1) {
            return [new Clause([], true)];
        }

        $possibilities = [];

        if ($left_clauses[0]->wedge && $right_clauses[0]->wedge) {
            return [new Clause([], true)];
        }

        $can_reconcile = true;

        if ($left_clauses[0]->wedge ||
            $right_clauses[0]->wedge ||
            !$left_clauses[0]->reconcilable ||
            !$right_clauses[0]->reconcilable
        ) {
            $can_reconcile = false;
        }

        foreach ($left_clauses[0]->possibilities as $var => $possible_types) {
            if (isset($possibilities[$var])) {
                $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
            } else {
                $possibilities[$var] = $possible_types;
            }
        }

        foreach ($right_clauses[0]->possibilities as $var => $possible_types) {
            if (isset($possibilities[$var])) {
                $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
            } else {
                $possibilities[$var] = $possible_types;
            }
        }

        return [new Clause($possibilities, false, $can_reconcile)];
    }
}
