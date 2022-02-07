<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Case_;
use Psalm\Node\VirtualNode;

final class VirtualCase extends Case_ implements VirtualNode
{

}
