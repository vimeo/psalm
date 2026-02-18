<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Param;

/**
 * @psalm-immutable
 */
final class VirtualParam extends Param implements VirtualNode
{

}
