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
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_keys;
use function preg_match;

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
