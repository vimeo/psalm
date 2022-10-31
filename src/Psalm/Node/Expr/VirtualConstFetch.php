<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ConstFetch;
use Psalm\Node\VirtualNode;

final class VirtualConstFetch extends ConstFetch implements VirtualNode
{

}
