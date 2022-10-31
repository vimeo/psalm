<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\ShiftLeft;
use Psalm\Node\VirtualNode;

final class VirtualShiftLeft extends ShiftLeft implements VirtualNode
{

}
