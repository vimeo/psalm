<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\While_;
use Psalm\Node\VirtualNode;

final class VirtualWhile extends While_ implements VirtualNode
{

}
