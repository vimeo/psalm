<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Const_;
use Psalm\Node\VirtualNode;

final class VirtualConst extends Const_ implements VirtualNode
{

}
