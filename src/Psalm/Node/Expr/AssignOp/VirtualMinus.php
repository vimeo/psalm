<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Minus;
use Psalm\Node\VirtualNode;

final class VirtualMinus extends Minus implements VirtualNode
{

}
