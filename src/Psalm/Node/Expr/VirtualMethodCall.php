<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\MethodCall;
use Psalm\Node\VirtualNode;

final class VirtualMethodCall extends MethodCall implements VirtualNode
{

}
