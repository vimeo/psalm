<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\BooleanNot;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualBooleanNot extends BooleanNot implements VirtualNode
{

}
