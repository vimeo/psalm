<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ErrorSuppress;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualErrorSuppress extends ErrorSuppress implements VirtualNode
{

}
