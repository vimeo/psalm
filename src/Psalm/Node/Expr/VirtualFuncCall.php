<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Node\VirtualNode;

final class VirtualFuncCall extends FuncCall implements VirtualNode
{

}
