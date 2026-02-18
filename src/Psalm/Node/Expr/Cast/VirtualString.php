<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast\String_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualString extends String_ implements VirtualNode
{

}
