<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\PreInc;
use Psalm\Node\VirtualNode;

final class VirtualPreInc extends PreInc implements VirtualNode
{

}
