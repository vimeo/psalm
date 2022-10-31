<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Spaceship;
use Psalm\Node\VirtualNode;

final class VirtualSpaceship extends Spaceship implements VirtualNode
{

}
