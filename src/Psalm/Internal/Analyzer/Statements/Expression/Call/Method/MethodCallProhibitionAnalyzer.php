<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CliOutput;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InternalMethod;
use Psalm\IssueBuffer;

/**
 * @internal
 */
final class MethodCallProhibitionAnalyzer
{
    /**
     * @param  string[]     $suppressed_issues
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        MethodIdentifier $method_id,
        ?string $caller_identifier,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $codebase_methods = $codebase->methods;

        CliOutput::checkMethodCall($statements_analyzer, $context, $method_id, $code_location);
        self::checkBuiltinDeprecation($statements_analyzer, $method_id, $code_location, $suppressed_issues);

        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return;
        }

        $storage = $codebase_methods->getStorage($method_id);

        if ($storage->deprecated) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod(
                    'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                        ' has been marked as deprecated',
                    $code_location,
                    (string) $method_id,
                ),
                $suppressed_issues,
            );
        }

        if (!$context->collect_initializations
        ) {
            if (!NamespaceAnalyzer::isWithinAny($caller_identifier ?? "", $storage->internal)) {
                IssueBuffer::maybeAdd(
                    new InternalMethod(
                        'The method ' . $codebase_methods->getCasedMethodId($method_id)
                            . ' is internal to ' . InternalClass::listToPhrase($storage->internal)
                            . ' but called from ' . ($caller_identifier ?: 'root namespace'),
                        $code_location,
                        (string) $method_id,
                    ),
                    $suppressed_issues,
                );
            }
        }
    }

    /**
     * Reports a call of a builtin method a PHP version deprecates (see PhpDeprecations)
     *
     * @param  string[]     $suppressed_issues
     */
    public static function checkBuiltinDeprecation(
        StatementsAnalyzer $statements_analyzer,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $codebase_methods = $statements_analyzer->getCodebase()->methods;
        $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if ($declaring_method_id === null) {
            return;
        }

        $deprecation_message = PhpDeprecations::getMethodMessage(
            (string) $declaring_method_id,
            $codebase_methods->getCasedMethodId($declaring_method_id),
        );

        if ($deprecation_message !== null) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod($deprecation_message, $code_location, (string) $declaring_method_id),
                $suppressed_issues,
            );
        }
    }
}
