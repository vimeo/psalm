<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

use function strtolower;

/**
 * Marks the `static` at any depth of a type as the one of the code the type comes from: where a template is bound to
 * it, `static` stays that of the caller and isn't resolved to the class the called method is declared in. A `static`
 * of a class related to the called one (a `static` a class declares for itself, as in `@use T<static>`) is the
 * called object's, so it is left to be resolved.
 *
 * @internal
 */
final class StaticResolver extends MutableTypeVisitor
{
    private string $called_class_lc;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(private readonly Codebase $codebase, string $called_class)
    {
        $this->called_class_lc = strtolower($called_class);
    }

    /**
     * @psalm-impure
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TNamedObject
            && $type->is_static
            && !$type->is_static_resolved
            && !$this->isRelatedToTheCalledClass($type->value)
        ) {
            $type = $type->setIsStatic(true, true);
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    private function isRelatedToTheCalledClass(string $class): bool
    {
        $class_lc = strtolower($class);

        return $class_lc === $this->called_class_lc
            || $this->codebase->classExtendsOrImplements($class_lc, $this->called_class_lc)
            || $this->codebase->classExtendsOrImplements($this->called_class_lc, $class_lc);
    }
}
