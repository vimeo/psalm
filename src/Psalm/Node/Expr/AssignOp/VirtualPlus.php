<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Plus;
use Psalm\Node\VirtualNode;

final class VirtualPlus extends Plus implements VirtualNode
{

}
