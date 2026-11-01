<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Arg;

/**
 * The left-hand side of a pipe (`|>`), passed as an argument of the call the pipe makes. PHP passes it by value, so it
 * can't bind to a by-reference parameter.
 *
 * @internal
 */
final class VirtualPipeArg extends Arg implements VirtualNode
{

}
