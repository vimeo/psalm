<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Smaller;
use Psalm\Node\VirtualNode;

final class VirtualSmaller extends Smaller implements VirtualNode
{

}
