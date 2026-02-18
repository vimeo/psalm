<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar\MagicConst;

use PhpParser\Node\Scalar\MagicConst\Line;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualLine extends Line implements VirtualNode
{

}
