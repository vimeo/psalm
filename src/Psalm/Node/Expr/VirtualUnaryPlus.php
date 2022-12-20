<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\UnaryPlus;
use Psalm\Node\VirtualNode;

final class VirtualUnaryPlus extends UnaryPlus implements VirtualNode
{

}
