<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * @internal
 */
final class FromDocblockSetter extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly bool $from_docblock,
        /** @var ?class-string<Atomic> the only atomics to set, with the rest left as they are */
        private readonly ?string $only_atomic_class = null,
    ) {
    }

    /**
     * Marks the `resource` atomics of a template bound or default as written in a docblock, the only place they are
     * valid, leaving the rest as it is
     */
    public static function markResources(Union $type): Union
    {
        $mutable_type = $type->getBuilder();
        (new self(true, TResource::class))->traverse($mutable_type);

        return $mutable_type->freeze();
    }

    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Atomic && !$type instanceof Union && !$type instanceof MutableUnion) {
            return null;
        }
        if ($this->only_atomic_class !== null && !$type instanceof $this->only_atomic_class) {
            return null;
        }
        if ($type->from_docblock === $this->from_docblock) {
            return null;
        }
        if ($type instanceof MutableUnion) {
            $type->from_docblock = true;
        } elseif ($type instanceof Union) {
            $type = $type->setProperties(['from_docblock' => $this->from_docblock]);
        } else {
            $type = $type->setFromDocblock($this->from_docblock);
        }

        if ($type instanceof TTemplateParam
            && $type->as->isMixed()
        ) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
