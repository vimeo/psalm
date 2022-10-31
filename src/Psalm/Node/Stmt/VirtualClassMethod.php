<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Node\VirtualNode;

final class VirtualClassMethod extends ClassMethod implements VirtualNode
{

}
