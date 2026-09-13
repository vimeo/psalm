<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\StaticPropertyFetch;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualStaticPropertyFetch extends StaticPropertyFetch implements VirtualNode
{

}
