<?php

namespace Psalm\Example\Plugin;

use Exception;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Interner;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\StrId;

use function end;
use function explode;

/**
 * Checks that functions and methods are correctly-cased
 */
final class FunctionCasingChecker implements AfterFunctionCallAnalysisInterface, AfterMethodCallAnalysisInterface
{
    #[\Override]
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        $expr = $event->getExpr();
        $codebase = $event->getCodebase();
        $declaring_method_id = $event->getDeclaringMethodId();
        $statements_source = $event->getStatementsSource();
        if (!$expr->name instanceof PhpParser\Node\Identifier) {
            return;
        }

        try {
            $function_storage = $codebase->methods->getStorage($declaring_method_id);

            if ($function_storage->cased_name === null) {
                return;
            }

            if ($function_storage->cased_name === StrId::__call) {
                return;
            }

            if ($function_storage->cased_name === StrId::__callStatic) {
                return;
            }

            $cased_name = Interner::str($function_storage->cased_name);

            if ($cased_name !== (string)$expr->name) {
                IssueBuffer::maybeAdd(
                    new IncorrectFunctionCasing(
                        'Function is incorrectly cased, expecting ' . $cased_name,
                        new CodeLocation($statements_source, $expr->name),
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }
        } catch (Exception) {
            // can throw if storage is missing
        }
    }

    #[\Override]
    public static function afterFunctionCallAnalysis(AfterFunctionCallAnalysisEvent $event): void
    {
        $expr = $event->getExpr();
        $codebase = $event->getCodebase();
        $statements_source = $event->getStatementsSource();
        $function_id = $event->getFunctionId();
        if ($expr->name instanceof PhpParser\Node\Expr) {
            return;
        }

        try {
            $function_storage = $codebase->functions->getStorage(
                $statements_source instanceof StatementsAnalyzer
                    ? $statements_source
                    : null,
                Interner::lower($function_id),
            );

            if ($function_storage->cased_name === null) {
                return;
            }

            $cased_name = Interner::str($function_storage->cased_name);
            $function_name_parts = explode('\\', $cased_name);

            if (end($function_name_parts) !== $expr->name->getLast()) {
                IssueBuffer::maybeAdd(
                    new IncorrectFunctionCasing(
                        'Function is incorrectly cased, expecting ' . $cased_name,
                        new CodeLocation($statements_source, $expr->name),
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }
        } catch (Exception) {
            // can throw if storage is missing
        }
    }
}

/**
 * 
 */
final class IncorrectFunctionCasing extends PluginIssue
{
}
