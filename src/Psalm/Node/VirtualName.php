<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Name;

/**
 * @psalm-immutable
 */
final class VirtualName extends Name implements VirtualNode
{

}
