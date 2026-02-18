<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\DeclareItem;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualDeclareItem extends DeclareItem implements VirtualNode
{

}
