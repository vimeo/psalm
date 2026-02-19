<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Identifier;

/**
 * Represents a non-namespaced name. Namespaced names are represented using Name nodes.
 */
final class VirtualIdentifier extends Identifier implements VirtualNode
{

}
