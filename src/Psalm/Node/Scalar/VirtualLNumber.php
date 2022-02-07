<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\LNumber;
use Psalm\Node\VirtualNode;

final class VirtualLNumber extends LNumber implements VirtualNode
{

}
