<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\List_;
use Psalm\Node\VirtualNode;

final class VirtualList extends List_ implements VirtualNode
{

}
