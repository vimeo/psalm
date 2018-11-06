<?php
namespace Psalm\Internal\Visitor;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;

/**
 * Shifts all nodes in a given AST by a set amount
 */
class OffsetShifterVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /** @var int */
    private $file_offset;

    /** @var int */
    private $line_offset;

    public function __construct(int $offset, int $line_offset)
    {
        $this->file_offset = $offset;
        $this->line_offset = $line_offset;
    }

    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        $attrs = $node->getAttributes();

        if ($cs = $node->getComments()) {
            $new_comments = [];

            foreach ($cs as $c) {
                if ($c instanceof PhpParser\Comment\Doc) {
                    $new_comments[] = new PhpParser\Comment\Doc(
                        $c->getText(),
                        $c->getLine() + $this->line_offset,
                        $c->getFilePos() + $this->file_offset
                    );
                } else {
                    $new_comments[] = new PhpParser\Comment(
                        $c->getText(),
                        $c->getLine() + $this->line_offset,
                        $c->getFilePos() + $this->file_offset
                    );
                }
            }

            $node->setAttribute('comments', $new_comments);
        }

        /** @psalm-suppress MixedOperand */
        $node->setAttribute('startFilePos', $attrs['startFilePos'] + $this->file_offset);
        /** @psalm-suppress MixedOperand */
        $node->setAttribute('endFilePos', $attrs['endFilePos'] + $this->file_offset);
        /** @psalm-suppress MixedOperand */
        $node->setAttribute('startLine', $attrs['startLine'] + $this->line_offset);
    }
}
