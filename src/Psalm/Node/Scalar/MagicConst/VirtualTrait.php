<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar\MagicConst;

use PhpParser\Node\Scalar\MagicConst\Trait_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualTrait extends Trait_ implements VirtualNode
{

}
