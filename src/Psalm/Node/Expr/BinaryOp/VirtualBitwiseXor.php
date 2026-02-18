<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualBitwiseXor extends BitwiseXor implements VirtualNode
{

}
