<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Coalesce;
use Psalm\Node\VirtualNode;

final class VirtualCoalesce extends Coalesce implements VirtualNode
{

}
