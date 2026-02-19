<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Psalm\Node\VirtualNode;

final class VirtualNotIdentical extends NotIdentical implements VirtualNode
{

}
