<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\String_;
use Psalm\Node\VirtualNode;

final class VirtualString extends String_ implements VirtualNode
{

}
