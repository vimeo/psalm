<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\Bool_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualBool extends Bool_ implements VirtualNode
{

}
