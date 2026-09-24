<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;

use function array_diff_key;
use function array_fill_keys;
use function is_array;
use function is_string;

/**
 * Finds, among the local variables of a function-like body, those that only ever hold objects
 * created there with `new` and never let them escape: no alias, no argument, no return value,
 * no capture. Such an object dies when its variable is unset or when the function-like ends,
 * so its destructor runs there.
 *
 * @internal
 */
final class FreshObjectFinder
{
    /** @var array<string, true> */
    private readonly array $candidates;

    /** @var array<string, New_> the last `new` assigned to each variable */
    private array $created = [];

    /** @var array<string, true> the variables that hold something else or let an object escape */
    private array $rejected = [];

    /**
     * @param list<string> $var_ids
     * @psalm-mutation-free
     */
    private function __construct(array $var_ids)
    {
        $this->candidates = array_fill_keys($var_ids, true);
    }

    /**
     * @param list<string> $var_ids
     * @param list<PhpParser\Node> $stmts
     * @return array<string, New_> the fresh variables with the `new` that created their object
     */
    public static function find(array $var_ids, array $stmts): array
    {
        $finder = new self($var_ids);

        foreach ($stmts as $stmt) {
            $finder->walk($stmt, null, false);
        }

        return array_diff_key($finder->created, $finder->rejected);
    }

    private function walk(PhpParser\Node $node, ?PhpParser\Node $parent, bool $captured): void
    {
        if ($node instanceof Variable) {
            if (is_string($node->name) && isset($this->candidates['$' . $node->name])) {
                $this->use('$' . $node->name, $node, $parent, $captured);
            }

            return;
        }

        if ($node instanceof Closure) {
            // the closure keeps whatever it captures; its body is another scope
            foreach ($node->uses as $use) {
                if (is_string($use->var->name)) {
                    $this->rejected['$' . $use->var->name] = true;
                }
            }

            return;
        }

        if ($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassLike) {
            return;
        }

        if ($node instanceof Stmt\Static_ || $node instanceof Stmt\Global_) {
            foreach ($node->vars as $var) {
                $var = $var instanceof Stmt\StaticVar ? $var->var : $var;

                if ($var instanceof Variable && is_string($var->name)) {
                    $this->rejected['$' . $var->name] = true;
                }
            }

            return;
        }

        if ($node instanceof ArrowFunction) {
            // an arrow function captures every variable it mentions
            $captured = true;
        }

        foreach ($node->getSubNodeNames() as $name) {
            /** @var mixed $sub_node */
            $sub_node = $node->$name;

            if ($sub_node instanceof PhpParser\Node) {
                $this->walk($sub_node, $node, $captured);
            } elseif (is_array($sub_node)) {
                /** @var mixed $item */
                foreach ($sub_node as $item) {
                    if ($item instanceof PhpParser\Node) {
                        $this->walk($item, $node, $captured);
                    }
                }
            }
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    private function use(string $var_id, Variable $node, ?PhpParser\Node $parent, bool $captured): void
    {
        if ($captured) {
            $this->rejected[$var_id] = true;

            return;
        }

        if ($parent instanceof Assign && $parent->var === $node) {
            if ($parent->expr instanceof New_) {
                $this->created[$var_id] = $parent->expr;
            } else {
                $this->rejected[$var_id] = true;
            }

            return;
        }

        // uses that look at the object without handing it to anyone
        if ((($parent instanceof PhpParser\Node\Expr\MethodCall
                    || $parent instanceof PhpParser\Node\Expr\NullsafeMethodCall
                    || $parent instanceof PhpParser\Node\Expr\PropertyFetch
                    || $parent instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                    || $parent instanceof PhpParser\Node\Expr\ArrayDimFetch)
                && $parent->var === $node)
            || (($parent instanceof PhpParser\Node\Expr\StaticCall
                    || $parent instanceof PhpParser\Node\Expr\StaticPropertyFetch
                    || $parent instanceof PhpParser\Node\Expr\ClassConstFetch)
                && $parent->class === $node)
            || ($parent instanceof PhpParser\Node\Expr\Instanceof_ && $parent->expr === $node)
            || ($parent instanceof Stmt\Foreach_ && $parent->expr === $node)
            || $parent instanceof PhpParser\Node\Expr\Isset_
            || $parent instanceof PhpParser\Node\Expr\Empty_
            || $parent instanceof Stmt\Unset_
            || $parent instanceof PhpParser\Node\Expr\Clone_
            || $parent instanceof PhpParser\Node\Expr\Cast
            || $parent instanceof PhpParser\Node\Expr\BooleanNot
            || $parent instanceof PhpParser\Node\Expr\Print_
            || $parent instanceof Stmt\Echo_
            || $parent instanceof Stmt\Expression
            || ($parent instanceof BinaryOp && !$parent instanceof BinaryOp\Coalesce)
        ) {
            return;
        }

        $this->rejected[$var_id] = true;
    }
}
