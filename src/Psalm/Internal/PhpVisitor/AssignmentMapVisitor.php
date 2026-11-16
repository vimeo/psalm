<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;

use function array_map;
use function is_string;

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
     * @psalm-mutation-free
     */
    public function __construct(protected ?string $this_class_name)
    {
    }

    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\AssignRef) {
            foreach ($this->getAssignedRootVarIds($node->var) as $var_id) {
                $this->defined_var_ids[$var_id] = true;
            }

            return null;
        }

        if ($node instanceof PhpParser\Node\Expr\Assign) {
            $right_var_id = ExpressionIdentifier::getRootVarId($node->expr, $this->this_class_name);

            foreach ($this->getAssignedRootVarIds($node->var) as $left_var_id) {
                $this->assignment_map[$left_var_id][$right_var_id ?? 'isset'] = true;
            }

            // what the sides change themselves (`$x = $counts['one']++`, `$a[$i++] = 1`) is in the map too
            $inner_visitor = new self($this->this_class_name);
            $inner_traverser = new PhpParser\NodeTraverser;
            $inner_traverser->addVisitor($inner_visitor);
            $inner_traverser->traverse([$node->expr, $node->var]);

            foreach ($inner_visitor->assignment_map as $inner_var_id => $inner_depends_on) {
                $this->assignment_map[$inner_var_id] = ($this->assignment_map[$inner_var_id] ?? []) + $inner_depends_on;
            }

            $this->defined_var_ids += $inner_visitor->defined_var_ids;

            return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\PostInc
            || $node instanceof PhpParser\Node\Expr\PostDec
            || $node instanceof PhpParser\Node\Expr\PreInc
            || $node instanceof PhpParser\Node\Expr\PreDec
            || $node instanceof PhpParser\Node\Expr\AssignOp
        ) {
            $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

            if ($var_id !== null) {
                $this->assignment_map[$var_id][$var_id] = true;
            }

            return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\FuncCall
            || $node instanceof PhpParser\Node\Expr\MethodCall
            || $node instanceof PhpParser\Node\Expr\StaticCall
        ) {
            if (!$node->isPartialFunctionApplication()) {
                foreach ($node->getArgs() as $arg) {
                    $arg_var_id = ExpressionIdentifier::getRootVarId($arg->value, $this->this_class_name);

                    if ($arg_var_id !== null) {
                        $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                    }
                }
            }

            if ($node instanceof PhpParser\Node\Expr\MethodCall) {
                $var_id = ExpressionIdentifier::getRootVarId($node->var, $this->this_class_name);

                if ($var_id !== null) {
                    $this->assignment_map[$var_id]['isset'] = true;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Unset_) {
            foreach ($node->vars as $arg) {
                $arg_var_id = ExpressionIdentifier::getRootVarId($arg, $this->this_class_name);

                if ($arg_var_id !== null) {
                    $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Foreach_) {
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
     * @return array<string, array<string, bool>>
     */
    public function getAssignmentMap(): array
    {
        return $this->assignment_map;
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
