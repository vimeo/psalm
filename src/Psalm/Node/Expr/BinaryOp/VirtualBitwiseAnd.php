<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualBitwiseAnd extends BitwiseAnd implements VirtualNode
{

}
