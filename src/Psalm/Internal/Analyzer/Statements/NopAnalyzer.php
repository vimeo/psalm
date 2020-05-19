<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\InvalidDocblock;
use Psalm\IssueBuffer;

class NopAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Nop $stmt,
        Context $context
    ) : void {
        if (($doc_comment = $stmt->getDocComment()) && $parsed_docblock = $statements_analyzer->getParsedDocblock()) {
            $var_comments = [];

            try {
                $var_comments = CommentAnalyzer::arrayToDocblocks(
                    $doc_comment,
                    $parsed_docblock,
                    $statements_analyzer->getSource(),
                    $statements_analyzer->getSource()->getAliases(),
                    $statements_analyzer->getSource()->getTemplateTypeMap()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt, null, true)
                    )
                )) {
                    // fall through
                }
            }

            $codebase = $statements_analyzer->getCodebase();

            foreach ($var_comments as $var_comment) {
                if (!$var_comment->var_id || !$var_comment->type) {
                    continue;
                }

                $comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self,
                    $statements_analyzer->getParentFQCLN()
                );

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }
    }
}
