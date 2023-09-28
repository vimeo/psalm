<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\UnrecognizedStatement;
use Psalm\IssueBuffer;

use function in_array;

/**
 * @internal
 */
final class DeclareAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Declare_ $stmt,
        Context $context
    ): void {
        foreach ($stmt->declares as $declaration) {
            $declaration_key = (string) $declaration->key;

            if ($declaration_key === 'strict_types') {
                if ($stmt->stmts !== null) {
                    IssueBuffer::maybeAdd(
                        new UnrecognizedStatement(
                            'strict_types declaration must not use block mode',
                            new CodeLocation($statements_analyzer, $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                self::analyzeStrictTypesDeclaration($statements_analyzer, $declaration, $context);
            } elseif ($declaration_key === 'ticks') {
                self::analyzeTicksDeclaration($statements_analyzer, $declaration);
            } elseif ($declaration_key === 'encoding') {
                self::analyzeEncodingDeclaration($statements_analyzer, $declaration);
            } else {
                IssueBuffer::maybeAdd(
                    new UnrecognizedStatement(
                        'Psalm does not understand the declare statement ' . $declaration->key,
                        new CodeLocation($statements_analyzer, $declaration),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }
    }

    private static function analyzeStrictTypesDeclaration(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\DeclareDeclare $declaration,
        Context $context
    ): void {
        if (!$declaration->value instanceof PhpParser\Node\Scalar\LNumber
            || !in_array($declaration->value->value, [0, 1], true)
        ) {
            IssueBuffer::maybeAdd(
                new UnrecognizedStatement(
                    'strict_types declaration can only have 1 or 0 as a value',
                    new CodeLocation($statements_analyzer, $declaration),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        if ($declaration->value->value === 1) {
            $context->strict_types = true;
        }
    }

    private static function analyzeTicksDeclaration(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\DeclareDeclare $declaration
    ): void {
        if (!$declaration->value instanceof PhpParser\Node\Scalar\LNumber) {
            IssueBuffer::maybeAdd(
                new UnrecognizedStatement(
                    'ticks declaration should have integer as a value',
                    new CodeLocation($statements_analyzer, $declaration),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    private static function analyzeEncodingDeclaration(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\DeclareDeclare $declaration
    ): void {
        if (!$declaration->value instanceof PhpParser\Node\Scalar\String_) {
            IssueBuffer::maybeAdd(
                new UnrecognizedStatement(
                    'encoding declaration should have string as a value',
                    new CodeLocation($statements_analyzer, $declaration),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }
}
