<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\HaltCompiler;
use Psalm\Node\VirtualNode;

final class VirtualHaltCompiler extends HaltCompiler implements VirtualNode
{

}
