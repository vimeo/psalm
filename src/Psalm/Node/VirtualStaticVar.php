<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\StaticVar;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualStaticVar extends StaticVar implements VirtualNode
{

}
