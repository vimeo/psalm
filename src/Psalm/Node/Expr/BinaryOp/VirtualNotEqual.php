<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\NotEqual;
use Psalm\Node\VirtualNode;

final class VirtualNotEqual extends NotEqual implements VirtualNode
{

}
