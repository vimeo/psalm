<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\StaticCall;
use Psalm\Node\VirtualNode;

final class VirtualStaticCall extends StaticCall implements VirtualNode
{

}
