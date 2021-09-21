<?php declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use Psalm\Node\VirtualNode;

/**
 * @property Node\Name $namespacedName Namespaced name (if using NameResolver)
 */
class VirtualFunction extends Function_ implements VirtualNode
{

}
