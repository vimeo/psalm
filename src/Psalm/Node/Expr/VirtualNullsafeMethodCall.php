<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\NullsafeMethodCall;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualNullsafeMethodCall extends NullsafeMethodCall implements VirtualNode
{

}
