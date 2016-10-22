<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Type;

class TryChecker
{
    /**
     * @return false|null
     */
    public static function check(StatementsChecker $statements_checker, PhpParser\Node\Stmt\TryCatch $stmt, Context $context, Context $loop_context = null)
    {
        $statements_checker->check($stmt->stmts, $context, $loop_context);

        // clone context for catches after running the try block, as
        // we optimistically assume it only failed at the very end
        $original_context = clone $context;

        foreach ($stmt->catches as $catch) {
            $catch_context = clone $original_context;

            $catch_class = ClassLikeChecker::getAbsoluteClassFromName(
                $catch->type,
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );

            if ($context->check_classes) {
                $absolute_class = $catch_class;

                if (ClassLikeChecker::checkAbsoluteClassOrInterface(
                    $absolute_class,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                    ) === false
                ) {
                    return false;
                }
            }

            $catch_context->vars_in_scope['$' . $catch->var] = new Type\Union([
                new Type\Atomic($catch_class)
            ]);

            $catch_context->vars_possibly_in_scope['$' . $catch->var] = true;

            $statements_checker->registerVariable('$' . $catch->var, $catch->getLine());

            $statements_checker->check($catch->stmts, $catch_context, $loop_context);

            if (!ScopeChecker::doesAlwaysReturnOrThrow($catch->stmts)) {
                foreach ($catch_context->vars_in_scope as $catch_var => $type) {
                    if ($catch->var !== $catch_var && isset($context->vars_in_scope[$catch_var]) && (string) $context->vars_in_scope[$catch_var] !== (string) $type) {
                        $context->vars_in_scope[$catch_var] = Type::combineUnionTypes($context->vars_in_scope[$catch_var], $type);
                    }
                }

                $context->vars_possibly_in_scope = array_merge($catch_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
            }
        }

        if ($stmt->finallyStmts) {
            $statements_checker->check($stmt->finallyStmts, $context, $loop_context);
        }
    }
}
