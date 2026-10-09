<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\FreshObjectFinder;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_keys;
use function explode;
use function preg_match;
use function str_contains;
use function str_ends_with;

/**
 * Destroying an object calls its destructor, an implicit call like `__toString` or `offsetGet`:
 * it happens where the object dies, which is where its last holder is unset, dropped as a
 * temporary, or goes out of scope.
 *
 * @internal
 */
final class DestructorAnalyzer
{
    /**
     * The destructor an instance of a class runs, own or inherited.
     *
     * @psalm-mutation-free
     */
    public static function getDestructorId(Codebase $codebase, string $fq_class_name): ?MethodIdentifier
    {
        if (!$codebase->classlike_storage_provider->has($fq_class_name)) {
            return null;
        }

        return $codebase->classlike_storage_provider->get($fq_class_name)->declaring_method_ids['__destruct'] ?? null;
    }

    /**
     * Charges the destructors of the objects a value of $type may hold, since the value is
     * destroyed here. The destroyed object is the destructor's own: what it does to it is fine.
     */
    public static function chargeDestruction(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Union $type,
        string $what,
        PhpParser\Node $node,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TNamedObject) {
                continue;
            }

            $destructor_id = self::getDestructorId($codebase, $atomic_type->value);

            if ($destructor_id === null) {
                continue;
            }

            $destructor = $codebase->methods->getStorage($destructor_id);

            $statements_analyzer->signalMutation(
                $destructor->capabilities & ~Capabilities::RECEIVER_LOCAL,
                $context,
                'destroying ' . $what . ' (' . $codebase->methods->getCasedMethodId($destructor_id) . ')',
                ImpureMethodCall::class,
                $node,
                $destructor->capabilities,
                false,
                $destructor,
                true,
            );
        }
    }

    /**
     * Charges the destructors of the objects the target of an assignment held before it: the
     * assignment drops them. Only the declared destructors of known classes count: what a
     * mixed, object or template value holds is unknown.
     */
    public static function chargeOverwrite(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\Expr $assign_var,
        ?string $var_id,
        Union $assign_value_type,
    ): void {
        $held_type = self::getHeldType($statements_analyzer, $context, $assign_var, $assign_value_type);

        if ($held_type === null) {
            return;
        }

        self::chargeDestruction(
            $statements_analyzer,
            $context,
            $held_type,
            'the old value of ' . ($var_id ?? 'the assigned variable'),
            $assign_var,
        );
    }

    /**
     * What a variable, property or array element holds before it is assigned, as far as the
     * context knows it without analysing the target: its type in scope, else the declared type
     * of the property or the value type of the array holding it. An appended element holds
     * nothing.
     */
    private static function getHeldType(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\Expr $expr,
        ?Union $assign_value_type = null,
    ): ?Union {
        $var_id = ExpressionIdentifier::getExtendedVarId(
            $expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($expr instanceof PhpParser\Node\Expr\PropertyFetch
            && $expr->var instanceof PhpParser\Node\Expr\Variable
            && $expr->var->name === 'this'
            && $context->calling_method_id !== null
            && (str_ends_with($context->calling_method_id, '::__construct')
                || str_ends_with($context->calling_method_id, '::__clone'))
        ) {
            // a property a constructor sets is still uninitialized or holds its default (never an
            // object with a destructor), and the original of a clone still holds what it resets
            return null;
        }

        if ($var_id !== null && isset($context->vars_in_scope[$var_id])) {
            return $context->vars_in_scope[$var_id];
        }

        if ($expr instanceof PhpParser\Node\Expr\Variable) {
            // a variable first assigned in a loop body holds the previous iteration's value, which
            // the loop analysis forgets: assume one like the value assigned now
            return $var_id !== null
                && $context->loop_scope !== null
                && isset($context->vars_possibly_in_scope[$var_id])
                ? $assign_value_type
                : null;
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($expr instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            if ($var_id === null || !str_contains($var_id, '::$')) {
                return null;
            }

            [$fq_class_name, $property_name] = explode('::$', $var_id, 2);

            return self::getDeclaredPropertyType($statements_analyzer, $fq_class_name, $property_name);
        }

        if ($expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (!$expr->name instanceof PhpParser\Node\Identifier) {
                return null;
            }

            $object_type = self::getHeldType($statements_analyzer, $context, $expr->var);

            if ($object_type === null) {
                return null;
            }

            $property_types = [];

            foreach ($object_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    $property_type = self::getDeclaredPropertyType(
                        $statements_analyzer,
                        $atomic_type->value,
                        $expr->name->name,
                    );

                    if ($property_type !== null) {
                        $property_types[] = $property_type;
                    }
                }
            }

            return $property_types ? Type::combineUnionTypeArray($property_types, $codebase) : null;
        }

        if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if ($expr->dim === null) {
                return null;
            }

            $array_type = self::getHeldType($statements_analyzer, $context, $expr->var);

            if ($array_type === null) {
                return null;
            }

            $value_types = [];

            foreach ($array_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TKeyedArray) {
                    $value_types[] = $atomic_type->getGenericValueType();
                } elseif ($atomic_type instanceof TArray) {
                    $value_types[] = $atomic_type->type_params[1];
                }
            }

            return $value_types ? Type::combineUnionTypeArray($value_types, $codebase) : null;
        }

        return null;
    }

    private static function getDeclaredPropertyType(
        StatementsAnalyzer $statements_analyzer,
        string $fq_class_name,
        string $property_name,
    ): ?Union {
        $storage_provider = $statements_analyzer->getCodebase()->classlike_storage_provider;

        if (!$storage_provider->has($fq_class_name)) {
            return null;
        }

        $declaring_class = $storage_provider->get($fq_class_name)->declaring_property_ids[$property_name] ?? null;

        if ($declaring_class === null || !$storage_provider->has($declaring_class)) {
            return null;
        }

        return $storage_provider->get($declaring_class)->properties[$property_name]->type ?? null;
    }

    /**
     * Charges the destructors of the objects created in a function-like body that are still held
     * by a local variable when it ends: they die with it. Only variables that held nothing but
     * objects created there with `new`, and never let them escape, count (see FreshObjectFinder).
     *
     * @param list<PhpParser\Node\Stmt> $stmts
     * @param array<string, true> $param_ids
     */
    public static function chargeDroppedObjects(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        array $stmts,
        array $param_ids,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $candidates = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if ($var_id === '$this'
                || isset($param_ids[$var_id])
                || isset($context->referenced_globals[$var_id])
                || !preg_match('/^\$[A-Za-z_\x80-\xff][\w\x80-\xff]*$/', $var_id)
            ) {
                continue;
            }

            foreach ($type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject
                    && self::getDestructorId($codebase, $atomic_type->value) !== null
                ) {
                    $candidates[$var_id] = $type;
                    break;
                }
            }
        }

        if (!$candidates) {
            return;
        }

        foreach (FreshObjectFinder::find(array_keys($candidates), $stmts) as $var_id => $new_expr) {
            self::chargeDestruction($statements_analyzer, $context, $candidates[$var_id], $var_id, $new_expr);
        }
    }
}
