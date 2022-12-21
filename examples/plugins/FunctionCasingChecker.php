<?php

namespace Psalm\Example\Plugin;

use Exception;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;

use function end;
use function explode;
use function strtolower;

/**
 * Checks that functions and methods are correctly-cased
 */
class FunctionCasingChecker implements AfterFunctionCallAnalysisInterface, AfterMethodCallAnalysisInterface
{
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
            /** @psalm-suppress ArgumentTypeCoercion */
            $method_id = new MethodIdentifier(...explode('::', $declaring_method_id));
            $function_storage = $codebase->methods->getStorage($method_id);

            if ($function_storage->cased_name === '__call') {
                return;
            }

            if ($function_storage->cased_name === '__callStatic') {
                return;
            }

            if ($function_storage->cased_name !== (string)$expr->name) {
                IssueBuffer::maybeAdd(
                    new IncorrectFunctionCasing(
                        'Function is incorrectly cased, expecting ' . $function_storage->cased_name,
                        new CodeLocation($statements_source, $expr->name),
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }
        } catch (Exception $e) {
            // can throw if storage is missing
        }
    }

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
                strtolower($function_id),
            );

            if (!$function_storage->cased_name) {
                return;
            }

            $function_name_parts = explode('\\', $function_storage->cased_name);

            if (end($function_name_parts) !== end($expr->name->parts)) {
                IssueBuffer::maybeAdd(
                    new IncorrectFunctionCasing(
                        'Function is incorrectly cased, expecting ' . $function_storage->cased_name,
                        new CodeLocation($statements_source, $expr->name),
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }
        } catch (Exception $e) {
            // can throw if storage is missing
        }
    }
}

class IncorrectFunctionCasing extends PluginIssue
{
}
