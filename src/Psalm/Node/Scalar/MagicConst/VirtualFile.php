<?php

declare(strict_types=1);

namespace Psalm\Node\Scalar\MagicConst;

use PhpParser\Node\Scalar\MagicConst\File;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualFile extends File implements VirtualNode
{

}
