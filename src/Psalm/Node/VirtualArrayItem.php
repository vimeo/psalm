<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\ArrayItem;
use Psalm\Node\VirtualNode;

final class VirtualArrayItem extends ArrayItem implements VirtualNode
{

}
