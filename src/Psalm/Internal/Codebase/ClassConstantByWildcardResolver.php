<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TMixed;

use function array_merge;

/**
 * @internal
 */
final class ClassConstantByWildcardResolver
{
    private StorageByPatternResolver $resolver;
    private Codebase $codebase;

    public function __construct(Codebase $codebase)
    {
        $this->resolver = new StorageByPatternResolver();
        $this->codebase = $codebase;
    }

    /**
     * @return non-empty-array<array-key,Atomic>|null
     */
    public function resolve(string $class_name, string $constant_pattern): ?array
    {
        if (!$this->codebase->classlike_storage_provider->has($class_name)) {
            return null;
        }

        $classlike_storage = $this->codebase->classlike_storage_provider->get($class_name);

        $constants = $this->resolver->resolveConstants(
            $classlike_storage,
            $constant_pattern,
        );

        $types = [];
        foreach ($constants as $class_constant_storage) {
            if (! $class_constant_storage->type) {
                $types[] = [new TMixed()];
                continue;
            }

            $types[] = $class_constant_storage->type->getAtomicTypes();
        }

        if ($types === []) {
            return null;
        }

        return array_merge([], ...$types);
    }
}
