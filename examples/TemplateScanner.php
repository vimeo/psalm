<?php
namespace Psalm\Examples\Template;

use PhpParser;
use Psalm;
use Psalm\Checker\CommentChecker;
use Psalm\Codebase;
use Psalm\Storage\FileStorage;

class TemplateScanner extends Psalm\Scanner\FileScanner
{
    const VIEW_CLASS = 'Your\\View\\Class';

    /**
     * @param array<mixed, PhpParser\Node> $stmts
     *
     * @return void
     */
    public function scan(Codebase $codebase, array $stmts, FileStorage $file_storage)
    {
        if (empty($stmts)) {
            return;
        }

        $first_stmt = $stmts[0];

        if (($first_stmt instanceof PhpParser\Node\Stmt\Nop) && ($doc_comment = $first_stmt->getDocComment())) {
            $comment_block = CommentChecker::parseDocComment(trim($doc_comment->getText()));

            if (isset($comment_block['specials']['variablesfrom'])) {
                $variables_from = trim($comment_block['specials']['variablesfrom'][0]);

                $first_line_regex = '/([A-Za-z\\\0-9]+::[a-z_A-Z]+)(\s+weak)?/';

                $matches = [];

                if (!preg_match($first_line_regex, $variables_from, $matches)) {
                    throw new \InvalidArgumentException('Could not interpret doc comment correctly');
                }

                /** @psalm-suppress MixedArgument */
                list($fq_class_name) = explode('::', $matches[1]);

                $codebase->queueClassLikeForScanning(
                    $fq_class_name,
                    $this->file_path,
                    true
                );
            }
        }

        $codebase->queueClassLikeForScanning(self::VIEW_CLASS, $this->file_path);

        parent::scan($codebase, $stmts, $file_storage);
    }
}
