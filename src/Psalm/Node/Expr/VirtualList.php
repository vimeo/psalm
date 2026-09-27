<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\List_;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualList extends List_ implements VirtualNode
{

}
