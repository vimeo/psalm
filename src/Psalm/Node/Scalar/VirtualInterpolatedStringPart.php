<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\InterpolatedStringPart;
use Psalm\Node\VirtualNode;

final class VirtualInterpolatedStringPart extends InterpolatedStringPart implements VirtualNode
{

}
