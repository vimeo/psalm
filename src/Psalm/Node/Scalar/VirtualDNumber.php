<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\DNumber;
use Psalm\Node\VirtualNode;

final class VirtualDNumber extends DNumber implements VirtualNode
{

}
