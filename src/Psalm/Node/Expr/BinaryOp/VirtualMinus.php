<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Minus;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualMinus extends Minus implements VirtualNode
{

}
