<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\ShiftRight;
use Psalm\Node\VirtualNode;

final class VirtualShiftRight extends ShiftRight implements VirtualNode
{

}
