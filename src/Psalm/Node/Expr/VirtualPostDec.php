<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\PostDec;
use Psalm\Node\VirtualNode;

final class VirtualPostDec extends PostDec implements VirtualNode
{

}
