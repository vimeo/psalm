<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Isset_;
use Psalm\Node\VirtualNode;

final class VirtualIsset extends Isset_ implements VirtualNode
{

}
