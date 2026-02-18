<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\ClosureUse;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualClosureUse extends ClosureUse implements VirtualNode
{

}
