<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt\TraitUseAdaptation;

use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use Psalm\Node\VirtualNode;

/**
 * @psalm-immutable
 */
final class VirtualAlias extends Alias implements VirtualNode
{

}
