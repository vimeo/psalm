<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Throw_;
use Psalm\Node\VirtualNode;

final class VirtualThrow extends Throw_ implements VirtualNode
{

}
