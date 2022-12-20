<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ClassConstFetch;
use Psalm\Node\VirtualNode;

final class VirtualClassConstFetch extends ClassConstFetch implements VirtualNode
{

}
