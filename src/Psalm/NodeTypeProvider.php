<?php

namespace Psalm;

use PhpParser;

interface NodeTypeProvider
{
    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function setType($node, \Psalm\Type\Union $type) : void;

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function getType($node) : ?\Psalm\Type\Union;
}
