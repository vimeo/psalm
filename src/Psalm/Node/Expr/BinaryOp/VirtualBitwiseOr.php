<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualBitwiseOr extends BitwiseOr implements VirtualNode
{

}
