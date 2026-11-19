<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

use function array_keys;

/**
 * Collects the variables captured by reference by the closures a value is, or contains, like the elements of an
 * array. The parameter and return types of callables are not values, so they are not looked into.
 *
 * @internal
 */
final class ClosureByRefUsesCollector extends TypeVisitor
{
    /** @var array<string, true> */
    private array $var_ids = [];

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TClosure) {
            foreach ($type->byref_uses as $use_name => $_) {
                $this->var_ids['$' . $use_name] = true;
            }

            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TCallable) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function collect(?TypeNode $type): array
    {
        if ($type === null) {
            return [];
        }

        $collector = new self();
        $collector->traverse($type);

        return array_keys($collector->var_ids);
    }
}
