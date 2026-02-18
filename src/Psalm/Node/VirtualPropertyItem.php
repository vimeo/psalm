<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\PropertyItem;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualPropertyItem extends PropertyItem implements VirtualNode
{

}
