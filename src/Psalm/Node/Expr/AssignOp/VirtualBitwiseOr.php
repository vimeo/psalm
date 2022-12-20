<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\BitwiseOr;
use Psalm\Node\VirtualNode;

final class VirtualBitwiseOr extends BitwiseOr implements VirtualNode
{

}
