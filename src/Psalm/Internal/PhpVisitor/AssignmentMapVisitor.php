<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;

use function array_diff_key;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function count;
use function is_string;
use function spl_object_id;

/**
 * @internal
 *
 * This produces a graph of probably assignments inside a loop
 *
 * With this map we can calculate how many times the loop analysis must
 * be run before all variables have the correct types
 */
final class AssignmentMapVisitor extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var array<string, array<string, bool>>
     */
    private array $assignment_map = [];

    /**
     * The variables defined otherwise than by an assignment (foreach, static, global, reference): the passes the
     * loop analysis needs are counted from the assignments only
     *
     * @var array<string, true>
     */
    private array $defined_var_ids = [];

    /**
     * The variables whose value goes into another one (by an assignment, a foreach or a reference) where they may
     * still hold what the previous iteration left in them: read before the loop assigns them, or through a reference
     *
     * @var array<string, true>
     */
    private array $feeding_var_ids = [];

    /**
     * The variables assigned, incremented, unset or iterated by reference: a call doesn't change the type of the
     * variables it is given other than by an assertion, which holds on the next iteration too
     *
     * @var array<string, true>
     */
    private array $written_var_ids = [];

    /**
     * The variables given to a call, which may write them through a by-reference parameter
     *
     * @var array<string, true>
     */
    private array $by_ref_arg_var_ids = [];

    /**
     * The expression statements of the loop body, evaluated on every iteration that gets to them
     *
     * @var array<int, true>
     */
    private array $statement_expr_ids = [];

    /**
     * The variables an expression statement of the loop body has overwritten so far
     *
     * @var array<string, true>
     */
    private array $overwritten_var_ids = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(protected ?string $this_class_name, private readonly ?self $outer_visitor = null)
    {
    }

    /**
     * @param array<PhpParser\Node> $nodes
     * @return null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function beforeTraverse(array $nodes): ?array
    {
        if ($this->outer_visitor === null) {
            foreach ($nodes as $node) {
                if ($node instanceof PhpParser\Node\Stmt\Expression) {
                    $this->statement_expr_ids[spl_object_id($node->expr)] = true;
                }
            }
        }

        return null;
    }

    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\AssignRef) {
            $right_var_id = ExpressionIdentifier::getRootVarId($node->expr, $this->this_class_name);

            foreach ($this->getAssignedRootVarIds($node->var) as $var_id) {
                $this->defined_var_ids[$var_id] = true;

                // what is written to either of them goes into the other one
                if ($right_var_id !== null && $right_var_id !== $var_id) {
                    $this->feeding_var_ids[$right_var_id] = true;
                    $this->feeding_var_ids[$var_id] = true;
                }
            }

            return null;
        }

        if ($node instanceof PhpParser\Node\Expr\Assign) {
            $right_var_id = ExpressionIdentifier::getRootVarId($node->expr, $this->this_class_name);

            $left_var_ids = $this->getAssignedRootVarIds($node->var);

            // `$a = [$b]`, `$a[$k] = 1`, `[$a, $b] = [$b, $c]`
            $read_var_ids = self::getReadVarIds([$node->expr, $node->var]);

            if (!$node->var instanceof PhpParser\Node\Expr\List_
                && !$node->var instanceof PhpParser\Node\Expr\Array_
            ) {
                $read_var_ids = array_diff_key($read_var_ids, array_flip($left_var_ids));
            }

            $this->addFeedingVarIds($read_var_ids);

            foreach ($left_var_ids as $left_var_id) {
                $this->assignment_map[$left_var_id][$right_var_id ?? 'isset'] = true;
                $this->written_var_ids[$left_var_id] = true;
            }

            // what the sides change themselves (`$x = $counts['one']++`, `$a[$i++] = 1`) is in the map too
            $inner_visitor = new self($this->this_class_name, $this);
            $inner_traverser = new PhpParser\NodeTraverser;
            $inner_traverser->addVisitor($inner_visitor);
            $inner_traverser->traverse([$node->expr, $node->var]);

            foreach ($inner_visitor->assignment_map as $inner_var_id => $inner_depends_on) {
                $this->assignment_map[$inner_var_id] = ($this->assignment_map[$inner_var_id] ?? []) + $inner_depends_on;
            }

            $this->defined_var_ids += $inner_visitor->defined_var_ids;
            $this->feeding_var_ids += $inner_visitor->feeding_var_ids;
            $this->written_var_ids += $inner_visitor->written_var_ids;
            $this->by_ref_arg_var_ids += $inner_visitor->by_ref_arg_var_ids;

            // the sides are evaluated before the variable is overwritten
            if ($node->var instanceof PhpParser\Node\Expr\Variable
                && is_string($node->var->name)
                && isset($this->statement_expr_ids[spl_object_id($node)])
            ) {
                $this->overwritten_var_ids['$' . $node->var->name] = true;
            }

            return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\PostInc
            || $node instanceof PhpParser\Node\Expr\PostDec
            || $node instanceof PhpParser\Node\Expr\PreInc
            || $node instanceof PhpParser\Node\Expr\PreDec
            || $node instanceof PhpParser\Node\Expr\AssignOp
        ) {
            $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

            if ($node instanceof PhpParser\Node\Expr\AssignOp) {
                // `$s .= $t`
                $this->addFeedingVarIds(array_diff_key(
                    self::getReadVarIds([$node->expr, $node->var]),
                    $var_id !== null ? [$var_id => true] : [],
                ));
            }

            if ($var_id !== null) {
                $this->assignment_map[$var_id][$var_id] = true;
                $this->written_var_ids[$var_id] = true;
            }

            return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if (($node instanceof PhpParser\Node\Expr\FuncCall
                || $node instanceof PhpParser\Node\Expr\MethodCall
                || $node instanceof PhpParser\Node\Expr\NullsafeMethodCall
                || $node instanceof PhpParser\Node\Expr\StaticCall
                || $node instanceof PhpParser\Node\Expr\New_)
            && !$node->isPartialFunctionApplication()
        ) {
            $args = $node->getArgs();

            foreach ($args as $arg) {
                $arg_var_id = ExpressionIdentifier::getRootVarId($arg->value, $this->this_class_name);

                if ($arg_var_id !== null) {
                    // left out of the map for constructors, which rarely write their arguments: the propagation
                    // passes follow such a write
                    if (!$node instanceof PhpParser\Node\Expr\New_) {
                        $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                    }

                    // possibly passed by reference
                    $this->by_ref_arg_var_ids[$arg_var_id] = true;
                }
            }

            // an argument may go into another one passed by reference (`array_push($b, $c)`)
            if (count($args) > 1) {
                $this->addFeedingVarIds(self::getReadVarIds(array_map(
                    static fn(PhpParser\Node\Arg $arg): PhpParser\Node\Expr => $arg->value,
                    $args,
                )));
            }
        }

        if ($node instanceof PhpParser\Node\Expr\MethodCall) {
            $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

            if ($var_id !== null) {
                $this->assignment_map[$var_id]['isset'] = true;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Unset_) {
            foreach ($node->vars as $arg) {
                $arg_var_id = ExpressionIdentifier::getRootVarId($arg, $this->this_class_name);

                if ($arg_var_id !== null) {
                    $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                    $this->written_var_ids[$arg_var_id] = true;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Foreach_) {
            $this->addFeedingVarIds(self::getReadVarIds([$node->expr]));

            if ($node->byRef) {
                // written through the value
                $iterated_var_id = ExpressionIdentifier::getRootVarId($node->expr, $this->this_class_name);

                if ($iterated_var_id !== null) {
                    $this->written_var_ids[$iterated_var_id] = true;
                }
            }

            foreach ([$node->keyVar, $node->valueVar] as $target) {
                if ($target) {
                    foreach ($this->getAssignedRootVarIds($target) as $var_id) {
                        $this->defined_var_ids[$var_id] = true;
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Static_) {
            foreach ($node->vars as $static_var) {
                if (is_string($static_var->var->name)) {
                    $this->defined_var_ids['$' . $static_var->var->name] = true;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Global_) {
            foreach ($node->vars as $global_var) {
                $var_id = ExpressionIdentifier::getRootVarId($global_var, $this->this_class_name);

                if ($var_id !== null) {
                    $this->defined_var_ids[$var_id] = true;
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getAssignedRootVarIds(PhpParser\Node\Expr $target): array
    {
        if ($target instanceof PhpParser\Node\Expr\List_ || $target instanceof PhpParser\Node\Expr\Array_) {
            $var_ids = [];

            foreach ($target->items as $item) {
                if ($item) {
                    $var_ids = [...$var_ids, ...$this->getAssignedRootVarIds($item->value)];
                }
            }

            return $var_ids;
        }

        $var_id = ExpressionIdentifier::getRootVarId($target, $this->this_class_name);

        return $var_id !== null ? [$var_id] : [];
    }

    /**
     * A variable read after an expression evaluated on every iteration overwrote it holds what this iteration put in
     * it, which the same pass carries
     *
     * @param array<string, true> $read_var_ids
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function addFeedingVarIds(array $read_var_ids): void
    {
        foreach ($read_var_ids as $var_id => $_) {
            if (!$this->isOverwritten($var_id)) {
                $this->feeding_var_ids[$var_id] = true;
            }
        }
    }

    /**
     * @psalm-mutation-free
     */
    private function isOverwritten(string $var_id): bool
    {
        return isset($this->overwritten_var_ids[$var_id]) || $this->outer_visitor?->isOverwritten($var_id);
    }

    /**
     * @param list<PhpParser\Node\Expr> $exprs
     * @return array<string, true>
     */
    private static function getReadVarIds(array $exprs): array
    {
        $var_ids = [];

        foreach ((new PhpParser\NodeFinder)->findInstanceOf($exprs, PhpParser\Node\Expr\Variable::class) as $var) {
            if (is_string($var->name)) {
                $var_ids['$' . $var->name] = true;
            }
        }

        return $var_ids;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getAssignmentMap(): array
    {
        return $this->assignment_map;
    }

    /**
     * The feeding variables the loop writes: only those change from one pass to the next
     *
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    public function getFeedingVarIds(): array
    {
        return array_intersect_key(
            $this->feeding_var_ids,
            $this->written_var_ids + $this->defined_var_ids + $this->by_ref_arg_var_ids,
        );
    }

    /**
     * @return array<string, true>
     */
    public function getDefinedVarIds(): array
    {
        return $this->defined_var_ids;
    }

    /**
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    public function getAssignedVarIds(): array
    {
        return array_map(static fn(array $_): bool => true, $this->assignment_map) + $this->defined_var_ids;
    }
}
