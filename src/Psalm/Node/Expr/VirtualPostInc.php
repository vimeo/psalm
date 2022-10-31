<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\PostInc;
use Psalm\Node\VirtualNode;

final class VirtualPostInc extends PostInc implements VirtualNode
{

}
