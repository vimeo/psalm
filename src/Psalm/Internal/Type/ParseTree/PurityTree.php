<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * The bracketed purity arguments of a type, as in Hack: `Traversable[pure]<int, string>`,
 * `iterable[P]`, `Closure[write-props|io](int): void`. Its parent is the {@see GenericTree} of
 * the type.
 *
 * @internal
 */
final class PurityTree extends ParseTree
{
}
