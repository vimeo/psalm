<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualLogicalXor extends LogicalXor implements VirtualNode
{

}
