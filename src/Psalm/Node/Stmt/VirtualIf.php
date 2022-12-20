<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\If_;
use Psalm\Node\VirtualNode;

final class VirtualIf extends If_ implements VirtualNode
{

}
