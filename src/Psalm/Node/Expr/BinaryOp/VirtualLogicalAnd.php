<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualLogicalAnd extends LogicalAnd implements VirtualNode
{

}
