<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Break_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualBreak extends Break_ implements VirtualNode
{

}
