<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\If_;

use function array_merge;
use function is_array;
use function strtolower;

/**
 * The properties of a class that its methods (other than the constructor) only read where an `isset()` of the
 * property holds: `if (isset($this->p)) { $this->p->f(); }`, `isset($this->p) && $this->p->f()`,
 * `isset($this->p) ? $this->p : null`, `$this->p ?? null`. Such a property may be left uninitialised on purpose.
 *
 * @internal
 */
final class IssetGuardedProperties
{
    /** @var array<string, true> */
    private array $checked = [];

    /** @var array<string, true> */
    private array $read_unguarded = [];

    /**
     * @param array<Node\Stmt> $class_stmts
     * @return array<string, true>
     */
    public static function find(array $class_stmts): array
    {
        $finder = new self();

        foreach ($class_stmts as $stmt) {
            $finder->walk($stmt, [], false);
        }

        $guarded = [];

        foreach ($finder->checked as $property => $_) {
            if (!isset($finder->read_unguarded[$property])) {
                $guarded[$property] = true;
            }
        }

        return $guarded;
    }

    /**
     * @param array<string, true> $guarded the properties an `isset()` holds for here
     */
    private function walk(Node $node, array $guarded, bool $everything_guarded): void
    {
        if ($node instanceof ClassMethod && strtolower($node->name->name) === '__construct') {
            return;
        }

        if ($node instanceof Isset_) {
            foreach ($node->vars as $var) {
                $property = self::getThisProperty($var);

                if ($property !== null) {
                    $this->checked[$property] = true;
                }

                $this->walkChecked($var, $guarded, $everything_guarded);
            }

            return;
        }

        if ($node instanceof Coalesce) {
            $this->walkChecked($node->left, $guarded, $everything_guarded);
            $this->walk($node->right, $guarded, $everything_guarded);

            return;
        }

        if ($node instanceof Assign) {
            // writing is no read
            if (self::getThisProperty($node->var) === null) {
                $this->walk($node->var, $guarded, $everything_guarded);
            }

            $this->walk($node->expr, $guarded, $everything_guarded);

            return;
        }

        if ($node instanceof BooleanAnd) {
            $this->walk($node->left, $guarded, $everything_guarded);
            $this->walk($node->right, $guarded + self::getIssetProperties($node->left), $everything_guarded);

            return;
        }

        if ($node instanceof Ternary) {
            $this->walk($node->cond, $guarded, $everything_guarded);

            if ($node->if !== null) {
                $this->walk($node->if, $guarded + self::getIssetProperties($node->cond), $everything_guarded);
            }

            $this->walk($node->else, $guarded, $everything_guarded);

            return;
        }

        if ($node instanceof If_) {
            $this->walk($node->cond, $guarded, $everything_guarded);

            foreach ($node->stmts as $stmt) {
                $this->walk($stmt, $guarded + self::getIssetProperties($node->cond), $everything_guarded);
            }

            foreach ($node->elseifs as $elseif) {
                $this->walk($elseif, $guarded, $everything_guarded);
            }

            if ($node->else !== null) {
                $this->walk($node->else, $guarded, $everything_guarded);
            }

            return;
        }

        if ($node instanceof ElseIf_) {
            $this->walk($node->cond, $guarded, $everything_guarded);

            foreach ($node->stmts as $stmt) {
                $this->walk($stmt, $guarded + self::getIssetProperties($node->cond), $everything_guarded);
            }

            return;
        }

        if ($node instanceof Else_) {
            $this->walkChildren($node, $guarded, $everything_guarded);

            return;
        }

        $property = self::getThisProperty($node);

        if ($property !== null && !$everything_guarded && !isset($guarded[$property])) {
            $this->read_unguarded[$property] = true;
        }

        $this->walkChildren($node, $guarded, $everything_guarded);
    }

    /**
     * An operand of `isset()` or `??`: only the fetches along it (`$this->p`, `$this->p->q`, `$this->p['k']`) are
     * checked, whatever else it is made of (an index, a call) is read as usual
     *
     * @param array<string, true> $guarded
     */
    private function walkChecked(Node $node, array $guarded, bool $everything_guarded): void
    {
        if (self::getThisProperty($node) !== null) {
            return;
        }

        if ($node instanceof PropertyFetch || $node instanceof NullsafePropertyFetch) {
            $this->walkChecked($node->var, $guarded, $everything_guarded);

            if (!$node->name instanceof Identifier) {
                $this->walk($node->name, $guarded, $everything_guarded);
            }

            return;
        }

        if ($node instanceof ArrayDimFetch) {
            $this->walkChecked($node->var, $guarded, $everything_guarded);

            if ($node->dim !== null) {
                $this->walk($node->dim, $guarded, $everything_guarded);
            }

            return;
        }

        $this->walk($node, $guarded, $everything_guarded);
    }

    /**
     * @param array<string, true> $guarded
     */
    private function walkChildren(Node $node, array $guarded, bool $everything_guarded): void
    {
        foreach ($node->getSubNodeNames() as $name) {
            /** @var mixed $child a sub node, a list of them or a scalar */
            $child = $node->{$name};

            if ($child instanceof Node) {
                $this->walk($child, $guarded, $everything_guarded);
            } elseif (is_array($child)) {
                /** @var mixed $item */
                foreach ($child as $item) {
                    if ($item instanceof Node) {
                        $this->walk($item, $guarded, $everything_guarded);
                    }
                }
            }
        }
    }

    /**
     * The properties a condition checks with `isset()`, alone or as the operands of `&&`
     *
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    private static function getIssetProperties(Node $condition): array
    {
        if ($condition instanceof Isset_) {
            $properties = [];

            foreach ($condition->vars as $var) {
                $property = self::getThisProperty($var);

                if ($property !== null) {
                    $properties[$property] = true;
                }
            }

            return $properties;
        }

        if ($condition instanceof BooleanAnd) {
            return array_merge(
                self::getIssetProperties($condition->left),
                self::getIssetProperties($condition->right),
            );
        }

        return [];
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getThisProperty(Node $node): ?string
    {
        return $node instanceof PropertyFetch
            && $node->var instanceof Variable
            && $node->var->name === 'this'
            && $node->name instanceof Identifier
            ? $node->name->name
            : null;
    }
}
