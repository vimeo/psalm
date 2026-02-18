<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Const_;

/**
 * @psalm-immutable
 */
final class VirtualConst extends Const_ implements VirtualNode
{

}
