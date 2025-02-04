<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Scanner\DocblockParser;

use function rtrim;
use function str_replace;
use function strlen;

/**
 * @internal
 */
final class ParamReplacementVisitor extends PhpParser\NodeVisitorAbstract
{
    /** @var list<FileManipulation> */
    private array $replacements = [];

    private bool $new_name_replaced = false;

    private bool $new_new_name_used = false;

    public function __construct(
        private readonly string $old_name,
        private readonly string $new_name,
    ) {
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\Variable) {
            if ($node->name === $this->old_name) {
                $this->replacements[] = new FileManipulation(
                    (int) $node->getAttribute('startFilePos') + 1,
                    (int) $node->getAttribute('endFilePos') + 1,
                    $this->new_name,
                );
            } elseif ($node->name === $this->new_name) {
                if ($this->new_new_name_used) {
                    $this->replacements = [];
                    return self::STOP_TRAVERSAL;
                }

                $this->replacements[] = new FileManipulation(
                    (int) $node->getAttribute('startFilePos') + 1,
                    (int) $node->getAttribute('endFilePos') + 1,
                    $this->new_name . '_new',
                );

                $this->new_name_replaced = true;
            } elseif ($node->name === $this->new_name . '_new') {
                if ($this->new_name_replaced) {
                    $this->replacements = [];
                    return self::STOP_TRAVERSAL;
                }

                $this->new_new_name_used = true;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassMethod
            && ($docblock = $node->getDocComment())
        ) {
            $parsed_docblock = DocblockParser::parse(
                $docblock->getText(),
                $docblock->getStartFilePos(),
            );

            $replaced = false;

            foreach ($parsed_docblock->tags as $tag_name => $tags) {
                foreach ($tags as $i => $tag) {
                    if ($tag_name === 'param'
                        || $tag_name === 'psalm-param'
                        || $tag_name === 'phpstan-param'
                        || $tag_name === 'phan-param'
                    ) {
                        $parts = CommentAnalyzer::splitDocLine($tag);

                        if (($parts[1] ?? '') === '$' . $this->old_name) {
                            $parsed_docblock->tags[$tag_name][$i] = str_replace(
                                '$' . $this->old_name,
                                '$' . $this->new_name,
                                $tag,
                            );
                            $replaced = true;
                        }
                    }
                }
            }

            if ($replaced) {
                $this->replacements[] = new FileManipulation(
                    $docblock->getStartFilePos(),
                    $docblock->getStartFilePos() + strlen($docblock->getText()),
                    rtrim($parsed_docblock->render($parsed_docblock->first_line_padding)),
                    false,
                    false,
                );
            }
        }

        return null;
    }

    /**
     * @return list<FileManipulation>
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }
}
