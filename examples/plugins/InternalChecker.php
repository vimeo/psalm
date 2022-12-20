<?php

namespace Psalm\Example\Plugin;

use Psalm\DocComment;
use Psalm\FileManipulation;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Issue\InternalClass;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;

use function strpos;

class InternalChecker implements AfterClassLikeAnalysisInterface
{
    /** @return null|false */
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event): ?bool
    {
        $storage = $event->getClasslikeStorage();
        if (!$storage->internal
            && strpos($storage->name, 'Psalm\\Internal') === 0
            && $storage->location
        ) {
            IssueBuffer::maybeAdd(
                new InternalClass(
                    "Class $storage->name must be marked @internal",
                    $storage->location,
                    $storage->name,
                ),
                $event->getStatementsSource()->getSuppressedIssues(),
                true,
            );

            if (!$event->getCodebase()->alter_code) {
                return null;
            }

            $stmt = $event->getStmt();
            $docblock = $stmt->getDocComment();
            if ($docblock) {
                $docblock_start = $docblock->getStartFilePos();
                $parsed_docblock = DocComment::parsePreservingLength($docblock);
            } else {
                $docblock_start = (int) $stmt->getAttribute('startFilePos');
                $parsed_docblock = new ParsedDocblock('', []);
            }
            $docblock_end = (int) $stmt->getAttribute('startFilePos');

            $parsed_docblock->tags['internal'] = [''];
            $new_docblock_content = $parsed_docblock->render('');
            $event->setFileReplacements([
                new FileManipulation($docblock_start, $docblock_end, $new_docblock_content),
            ]);
        }
        return null;
    }
}
