<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\AttributeGroup;

/**
 * @psalm-immutable
 */
final class VirtualAttributeGroup extends AttributeGroup implements VirtualNode
{

}
