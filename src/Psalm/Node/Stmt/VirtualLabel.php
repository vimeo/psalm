<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Label;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualLabel extends Label implements VirtualNode
{

}
