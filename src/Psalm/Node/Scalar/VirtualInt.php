<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\Int_;
use Psalm\Node\VirtualNode;

final class VirtualInt extends Int_ implements VirtualNode
{

}
