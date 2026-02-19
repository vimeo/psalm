<?php

declare(strict_types=1);

namespace Psalm\Node\Name;

use PhpParser\Node\Name\Relative;
use Psalm\Node\VirtualNode;

final class VirtualRelative extends Relative implements VirtualNode
{

}
