<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Clone_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualClone extends Clone_ implements VirtualNode
{

}
