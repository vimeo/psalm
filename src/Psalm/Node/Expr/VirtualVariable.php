<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Variable;
use Psalm\Node\VirtualNode;

final class VirtualVariable extends Variable implements VirtualNode
{

}
