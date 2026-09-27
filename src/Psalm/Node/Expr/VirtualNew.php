<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\New_;
use Psalm\Node\VirtualNode;

/**
 * @api
 */
final class VirtualNew extends New_ implements VirtualNode
{

}
