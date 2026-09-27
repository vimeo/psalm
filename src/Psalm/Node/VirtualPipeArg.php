<?php

declare(strict_types=1);

namespace Psalm\Node;

use PhpParser\Node\Arg;

/**
 * The left-hand side of a pipe (`|>`) expression, passed as the single argument of the piped call.
 *
 * It is always passed by value, so it can never bind to a by-ref parameter.
 */
final class VirtualPipeArg extends Arg implements VirtualNode
{

}
