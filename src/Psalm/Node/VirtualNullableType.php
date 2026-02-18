<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\NullableType;

/**
 * @psalm-immutable
 */
final class VirtualNullableType extends NullableType implements VirtualNode
{

}
