<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\UnionType;

/**
 * @psalm-immutable
 */
final class VirtualUnionType extends UnionType implements VirtualNode
{

}
