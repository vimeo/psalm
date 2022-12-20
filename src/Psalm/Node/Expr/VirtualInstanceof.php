<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Instanceof_;
use Psalm\Node\VirtualNode;

final class VirtualInstanceof extends Instanceof_ implements VirtualNode
{

}
