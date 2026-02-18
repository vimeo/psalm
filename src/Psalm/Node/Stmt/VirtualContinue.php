<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Continue_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualContinue extends Continue_ implements VirtualNode
{

}
