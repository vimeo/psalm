<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ArrayDimFetch;
use Psalm\Node\VirtualNode;

final class VirtualArrayDimFetch extends ArrayDimFetch implements VirtualNode
{

}
