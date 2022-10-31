<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\New_;
use Psalm\Node\VirtualNode;

final class VirtualNew extends New_ implements VirtualNode
{

}
