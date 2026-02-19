<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Identical;
use Psalm\Node\VirtualNode;

final class VirtualIdentical extends Identical implements VirtualNode
{

}
