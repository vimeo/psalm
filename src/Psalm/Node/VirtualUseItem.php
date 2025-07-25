<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\UseItem;
use Psalm\Node\VirtualNode;

final class VirtualUseItem extends UseItem implements VirtualNode
{

}
