<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Catch_;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualCatch extends Catch_ implements VirtualNode
{

}
