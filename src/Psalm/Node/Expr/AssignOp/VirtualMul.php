<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Mul;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualMul extends Mul implements VirtualNode
{

}
