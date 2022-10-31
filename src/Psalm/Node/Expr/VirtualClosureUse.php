<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ClosureUse;
use Psalm\Node\VirtualNode;

final class VirtualClosureUse extends ClosureUse implements VirtualNode
{

}
