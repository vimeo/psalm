<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\UseItem;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualUseItem extends UseItem implements VirtualNode
{

}
