<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\ShiftLeft;
use Psalm\Node\VirtualNode;

final class VirtualShiftLeft extends ShiftLeft implements VirtualNode
{

}
