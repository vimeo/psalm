<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\BitwiseNot;
use Psalm\Node\VirtualNode;

final class VirtualBitwiseNot extends BitwiseNot implements VirtualNode
{

}
