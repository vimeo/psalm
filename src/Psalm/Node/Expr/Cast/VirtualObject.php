<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\Object_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualObject extends Object_ implements VirtualNode
{

}
