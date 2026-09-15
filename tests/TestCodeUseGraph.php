<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Closure;
use Override;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Provider\ClassLikeStorageProvider;

/**
 * A {@see CodeUseGraph} with an injected out-of-project root predicate, so unit
 * tests can control root selection over synthetic node ids without real storages.
 *
 * CodeUseGraph is final; extending it works at runtime thanks to dg/bypass-finals
 * (tests/autoload.php). Psalm's static analysis still sees the final class, so the
 * resulting LSP/immutability complaints are suppressed here.
 *
 * @psalm-suppress InvalidExtendClass
 * @psalm-suppress MethodSignatureMismatch
 * @psalm-suppress ConstructorSignatureMismatch
 * @psalm-suppress ParamNameMismatch
 * @psalm-suppress ImplementedParamTypeMismatch
 * @psalm-suppress ImmutableDependency
 */
final class TestCodeUseGraph extends CodeUseGraph
{
    /**
     * @param Closure(string): bool $is_root
     */
    public function __construct(private readonly Closure $is_root)
    {
        parent::__construct(new ClassLikeStorageProvider(), new TestConfig());
    }

    #[Override]
    protected function isRoot(string $node_id): bool
    {
        return ($this->is_root)($node_id);
    }
}
