<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\AssignRef;
use Psalm\Node\VirtualNode;

final class VirtualAssignRef extends AssignRef implements VirtualNode
{

}
