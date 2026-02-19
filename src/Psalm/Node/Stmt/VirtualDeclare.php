<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Declare_;
use Psalm\Node\VirtualNode;

final class VirtualDeclare extends Declare_ implements VirtualNode
{

}
