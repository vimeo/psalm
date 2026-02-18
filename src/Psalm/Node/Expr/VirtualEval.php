<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Eval_;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualEval extends Eval_ implements VirtualNode
{

}
