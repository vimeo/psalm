<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\Float_;
use Psalm\Node\VirtualNode;

final class VirtualFloat extends Float_ implements VirtualNode
{

}
