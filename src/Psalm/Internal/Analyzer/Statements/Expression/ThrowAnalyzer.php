<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\TryScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidThrow;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_any;
use function strtolower;

/**
 * @internal
 */
final class ThrowAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Throw_ $stmt,
        Context $context,
    ): bool {
        $context->inside_throw = true;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->has_returned = true;
            return false;
        }
        $context->inside_throw = false;
        $context->has_returned = true;

        // a branch that throws never joins the rest of the try body, but where a catch that goes on after the try can
        // take the exception what it assigned can still be seen
        $thrown_type = $statements_analyzer->node_data->getType($stmt->expr);

        $try_scope = self::getCatchingTryScope(
            $statements_analyzer->getCodebase(),
            $context->try_scope,
            $thrown_type,
        );

        if ($try_scope !== null) {
            $continues_after_try = array_any(
                $try_scope->continuing_catch_classes,
                static fn(string $catch_class): bool => self::mayBeCaughtBy(
                    $statements_analyzer->getCodebase(),
                    $thrown_type,
                    $catch_class,
                ),
            );

            $throw_nodes = [];

            foreach ($context->assigned_var_ids as $var_id => $_) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $throw_nodes[$var_id] = $context->vars_in_scope[$var_id]->parent_nodes;

                    if ($continues_after_try) {
                        $try_scope->post_try_parent_nodes[$var_id] = [
                            ...($try_scope->post_try_parent_nodes[$var_id] ?? []),
                            ...$context->vars_in_scope[$var_id]->parent_nodes,
                        ];
                    }
                }
            }

            $try_scope->throws[] = ['type' => $thrown_type, 'nodes' => $throw_nodes];
        }

        if ($context->finally_scope) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                    $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $context->finally_scope->vars_in_scope[$var_id],
                        $type,
                        $statements_analyzer->getCodebase(),
                    );
                } else {
                    $context->finally_scope->vars_in_scope[$var_id] = $type->setPossiblyUndefined(true, true);
                }
            }
        }

        if ($context->check_classes
            && ($throw_type = $statements_analyzer->node_data->getType($stmt->expr))
            && !$throw_type->hasMixed()
        ) {
            $exception_type = new Union([new TNamedObject('Exception'), new TNamedObject('Throwable')]);

            $file_analyzer = $statements_analyzer->getFileAnalyzer();
            $codebase = $statements_analyzer->getCodebase();

            foreach ($throw_type->getAtomicTypes() as $throw_type_part) {
                $throw_type_candidate = new Union([$throw_type_part]);

                if (!UnionTypeComparator::isContainedBy($codebase, $throw_type_candidate, $exception_type)) {
                    if (IssueBuffer::accepts(
                        new InvalidThrow(
                            'Cannot throw ' . $throw_type_part
                                . ' as it does not extend Exception or implement Throwable',
                            new CodeLocation($file_analyzer, $stmt),
                            (string) $throw_type_part,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    )) {
                        return false;
                    }
                } elseif (!$context->isSuppressingExceptions($statements_analyzer)) {
                    $codelocation = new CodeLocation($file_analyzer, $stmt);
                    $hash = $codelocation->getHash();
                    foreach ($throw_type->getAtomicTypes() as $throw_atomic_type) {
                        if ($throw_atomic_type instanceof TNamedObject) {
                            $context->possibly_thrown_exceptions[$throw_atomic_type->value][$hash] = $codelocation;
                        }
                    }
                }
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getNever());

        return true;
    }

    /**
     * The innermost try with a catch that may take the thrown exception: the exception leaves the ones before it
     */
    private static function getCatchingTryScope(
        Codebase $codebase,
        ?TryScope $try_scope,
        ?Union $thrown_type,
    ): ?TryScope {
        while ($try_scope !== null) {
            foreach ($try_scope->catch_classes as $catch_class) {
                if (self::mayBeCaughtBy($codebase, $thrown_type, $catch_class)) {
                    return $try_scope;
                }
            }

            $try_scope = $try_scope->outer;
        }

        return null;
    }

    public static function mayBeCaughtBy(Codebase $codebase, ?Union $thrown_type, string $catch_class): bool
    {
        if ($thrown_type === null || !$codebase->classOrInterfaceExists($catch_class)) {
            return true;
        }

        foreach ($thrown_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TNamedObject
                || !$codebase->classOrInterfaceExists($atomic_type->value)
                || strtolower($atomic_type->value) === $catch_class
                || $codebase->classExtendsOrImplements($atomic_type->value, $catch_class)
                // a thrown supertype may be an instance of the caught class
                || $codebase->classExtendsOrImplements($catch_class, $atomic_type->value)
            ) {
                return true;
            }
        }

        return false;
    }
}
