<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\StaticVar;
use Psalm\Node\VirtualNode;

final class VirtualStaticVar extends StaticVar implements VirtualNode
{

}
