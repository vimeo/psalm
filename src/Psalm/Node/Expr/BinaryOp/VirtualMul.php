<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Mul;
use Psalm\Node\VirtualNode;

final class VirtualMul extends Mul implements VirtualNode
{

}
