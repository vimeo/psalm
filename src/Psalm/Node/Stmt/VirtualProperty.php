<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Property;
use Psalm\Node\VirtualNode;

final class VirtualProperty extends Property implements VirtualNode
{

}
