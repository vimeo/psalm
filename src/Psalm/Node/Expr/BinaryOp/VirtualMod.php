<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Mod;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualMod extends Mod implements VirtualNode
{

}
