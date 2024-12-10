<?php

declare(strict_types=1);

namespace Psalm;

use PhpParser;
use Psalm\Type\Union;

interface NodeTypeProvider
{
    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function setType(PhpParser\NodeAbstract $node, Union $type): void;

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function getType(PhpParser\NodeAbstract $node): ?Union;
}
