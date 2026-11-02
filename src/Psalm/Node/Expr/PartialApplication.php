<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\VariadicPlaceholder;

/**
 * A partial function application of PHP 8.6 (`f(1, ?)`). Psalm replaces the call node of one with this node when it
 * parses a file, so that no other node holds an argument placeholder: `$call` is the call in its first-class callable
 * form (`f(...)`), which creates the closure without calling anything, and `$args` are the arguments as written.
 *
 * @api
 */
final class PartialApplication extends Expr
{
    /**
     * @param list<Arg|ArgPlaceholder|VariadicPlaceholder> $args
     * @param array<string, mixed> $attributes
     * @psalm-capabilities read-props
     */
    public function __construct(
        public FuncCall|MethodCall|NullsafeMethodCall|StaticCall|New_ $call,
        public array $args,
        array $attributes = [],
    ) {
        $this->attributes = $attributes;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return ['call', 'args'];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getType(): string
    {
        return 'Expr_PartialApplication';
    }
}
