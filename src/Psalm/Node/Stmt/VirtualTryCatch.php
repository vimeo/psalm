<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\TryCatch;
use Psalm\Node\VirtualNode;

final class VirtualTryCatch extends TryCatch implements VirtualNode
{

}
