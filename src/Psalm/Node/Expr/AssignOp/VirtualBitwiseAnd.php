<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\BitwiseAnd;
use Psalm\Node\VirtualNode;

final class VirtualBitwiseAnd extends BitwiseAnd implements VirtualNode
{

}
