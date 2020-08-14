<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use Psalm\FileManipulation;

/**
 * @internal
 */
class ParamReplacementVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /** @var string */
    private $old_name;

    /** @var string */
    private $new_name;

    /** @var list<FileManipulation> */
    private $replacements = [];

    /** @var bool */
    private $new_name_replaced = false;

    /** @var bool */
    private $new_new_name_used = false;

    public function __construct(string $old_name, string $new_name)
    {
        $this->old_name = $old_name;
        $this->new_name = $new_name;
    }

    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Expr\Variable) {
            if ($node->name === $this->old_name) {
                $this->replacements[] = new FileManipulation(
                    (int) $node->getAttribute('startFilePos') + 1,
                    (int) $node->getAttribute('endFilePos') + 1,
                    $this->new_name
                );
            } elseif ($node->name === $this->new_name) {
                if ($this->new_new_name_used) {
                    $this->replacements = [];
                    return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                }

                $this->replacements[] = new FileManipulation(
                    (int) $node->getAttribute('startFilePos') + 1,
                    (int) $node->getAttribute('endFilePos') + 1,
                    $this->new_name . '_new'
                );

                $this->new_name_replaced = true;
            } elseif ($node->name === $this->new_name . '_new') {
                if ($this->new_name_replaced) {
                    $this->replacements = [];
                    return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                }

                $this->new_new_name_used = true;
            }
        }
    }

    /**
     * @return list<FileManipulation>
     */
    public function getReplacements()
    {
        return $this->replacements;
    }
}
