<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Foreach_;
use Psalm\Node\VirtualNode;

final class VirtualForeach extends Foreach_ implements VirtualNode
{

}
