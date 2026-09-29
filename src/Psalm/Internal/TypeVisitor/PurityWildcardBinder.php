<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Type\PurityWildcard;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Replaces every `_` purity in a type with a purity template.
 *
 * @internal
 */
final class PurityWildcardBinder extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props
     */
    public function __construct(
        private readonly TTemplateParam $template,
    ) {
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof Atomic && PurityWildcard::isPlaceholder($type)) {
            $type = $this->template;

            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
