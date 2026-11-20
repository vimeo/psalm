<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use PhpParser\Node\Expr;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * A constant expression reading class constants whose values are not known while its file is scanned: it is inferred
 * as written when it is read (see ConstantTypeResolver::inferExpression()). The expression has no attributes, and the
 * names of its classes are fully qualified.
 *
 * @internal
 * @psalm-immutable
 */
final class UnresolvedExpression extends UnresolvedConstantComponent
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param ?UnresolvedConstantComponent $fallback what the expression resolves to where the inference has no type
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly Expr $expr,
        public readonly ?string $namespace,
        public readonly ?string $fq_classlike_name,
        public readonly ?UnresolvedConstantComponent $fallback,
    ) {
    }
}
