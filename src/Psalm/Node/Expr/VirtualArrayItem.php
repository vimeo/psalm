<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ArrayItem;
use Psalm\Node\VirtualNode;

final class VirtualArrayItem extends ArrayItem implements VirtualNode
{

}
