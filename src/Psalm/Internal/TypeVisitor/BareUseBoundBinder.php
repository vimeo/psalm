<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TemplateDefaults;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Binds each class written without type arguments, at any depth of a type, to the bounds of its templates
 * ({@see TemplateDefaults::bindBareUse()}).
 *
 * @internal
 */
final class BareUseBoundBinder extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof TNamedObject) {
            return null;
        }

        $bound_use = TemplateDefaults::bindBareUse($type, $this->codebase, false);

        if ($bound_use === $type) {
            return null;
        }

        // the bounds of a class can name the class itself (`@template T of Node` in Node)
        $type = $bound_use;

        return self::DONT_TRAVERSE_CHILDREN;
    }
}
