<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Static_;
use Psalm\Node\VirtualNode;

final class VirtualStatic extends Static_ implements VirtualNode
{

}
