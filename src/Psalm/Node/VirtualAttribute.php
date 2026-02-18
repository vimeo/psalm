<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Attribute;

/**
 * @psalm-immutable
 */
final class VirtualAttribute extends Attribute implements VirtualNode
{

}
