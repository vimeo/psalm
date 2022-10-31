<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Plus;
use Psalm\Node\VirtualNode;

final class VirtualPlus extends Plus implements VirtualNode
{

}
