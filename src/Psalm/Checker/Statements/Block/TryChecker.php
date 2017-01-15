<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\CodeLocation;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

class TryChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\TryCatch    $stmt
     * @param   Context                         $context
     * @param   Context|null                    $loop_context
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\TryCatch $stmt,
        Context $context,
        Context $loop_context = null
    ) {
        $statements_checker->analyze($stmt->stmts, $context, $loop_context);

        // clone context for catches after running the try block, as
        // we optimistically assume it only failed at the very end
        $original_context = clone $context;

        foreach ($stmt->catches as $catch) {
            $catch_context = clone $original_context;

            $fq_catch_classes = [];

            foreach ($catch->types as $catch_type) {
                $fq_catch_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $catch_type,
                    $statements_checker
                );

                if ($context->check_classes) {
                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $fq_catch_class,
                        $statements_checker->getFileChecker(),
                        new CodeLocation($statements_checker->getSource(), $catch_type),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }

                $fq_catch_classes[] = $fq_catch_class;
            }

            $catch_context->vars_in_scope['$' . $catch->var] = new Type\Union(
                array_map(
                    /**
                     * @param string $fq_catch_class
                     * @return Type\Atomic
                     */
                    function ($fq_catch_class) {
                        return new TNamedObject($fq_catch_class);
                    },
                    $fq_catch_classes
                )
            );

            $catch_context->vars_possibly_in_scope['$' . $catch->var] = true;

            $statements_checker->registerVariable('$' . $catch->var, $catch->getLine());

            $statements_checker->analyze($catch->stmts, $catch_context, $loop_context);

            if (!ScopeChecker::doesAlwaysReturnOrThrow($catch->stmts)) {
                foreach ($catch_context->vars_in_scope as $catch_var => $type) {
                    if ($catch->var !== $catch_var &&
                        isset($context->vars_in_scope[$catch_var]) &&
                        (string) $context->vars_in_scope[$catch_var] !== (string) $type
                    ) {
                        $context->vars_in_scope[$catch_var] = Type::combineUnionTypes(
                            $context->vars_in_scope[$catch_var],
                            $type
                        );
                    }
                }

                $context->vars_possibly_in_scope = array_merge(
                    $catch_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );
            }
        }

        if ($stmt->finally) {
            $statements_checker->analyze($stmt->finally->stmts, $context, $loop_context);
        }

        return null;
    }
}
