<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Empty_;
use Psalm\Node\VirtualNode;

final class VirtualEmpty extends Empty_ implements VirtualNode
{

}
