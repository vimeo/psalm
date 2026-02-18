<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Ternary;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualTernary extends Ternary implements VirtualNode
{

}
