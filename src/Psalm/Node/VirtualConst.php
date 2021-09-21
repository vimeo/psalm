<?php declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Const_;
use PhpParser\Node\Name;

/**
 * @property Name $namespacedName Namespaced name (for global constants, if using NameResolver)
 */
class VirtualConst extends Const_ implements VirtualNode
{

}
