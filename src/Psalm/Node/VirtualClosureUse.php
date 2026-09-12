<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\ClosureUse;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualClosureUse extends ClosureUse implements VirtualNode
{

}
