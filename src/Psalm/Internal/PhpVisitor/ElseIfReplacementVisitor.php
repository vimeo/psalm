<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\If_;

/**
 * @internal
 */
class ElseIfReplacementVisitor extends PhpParser\NodeVisitorAbstract
{
    public function enterNode(PhpParser\Node $node): ?int
    {
        if (!$node instanceof If_ || !$node->elseifs) {
            return null;
        }

        $elseifs = $node->elseifs;
        $else = $node->else;
        $node->elseifs = [];
        while ($elseif = array_shift($elseifs)) {
            $node->else = new Else_(
                [
                    $if = new If_($elseif->cond, [
                        'stmts' => $elseif->stmts
                    ])
                ],
                $elseif->getAttributes()
            );
            $node = $if;
        }
        if ($else) {
            $node->else = $else;
        }

        return null;
    }
}
