<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Interface_;
use Psalm\Node\VirtualNode;

final class VirtualInterface extends Interface_ implements VirtualNode
{

}
