<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar;

use PhpParser\Node\Scalar\InterpolatedString;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualInterpolatedString extends InterpolatedString implements VirtualNode
{

}
