<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Interner;

/**
 * Denotes a `class-string` corresponding to a template parameter previously specified in a `@template` tag.
 *
 * @psalm-immutable
 * @api
 */
final class TTemplateParamClass extends TClassString
{
    /**
     * @param int $param_name interned template name
     * @param int $as interned class name of the template bound (StrId::object if none)
     * @param int $defining_class interned defining entity
     */
    public function __construct(
        public int $param_name,
        int $as,
        ?TNamedObject $as_type,
        public int $defining_class,
        bool $from_docblock = false,
    ) {
        parent::__construct(
            $as,
            $as_type,
            false,
            false,
            false,
            $from_docblock,
        );
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'class-string<' . Interner::str($this->param_name) . '>';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'class-string<' . Interner::str($this->param_name) . ':' . Interner::str($this->defining_class)
            . ' as ' . ($this->as_type ? $this->as_type->getId($exact) : Interner::str($this->as)) . '>';
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'class-string<' . Interner::str($this->param_name) . '>';
    }

    /**
     * @param  array<int, int> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        return Interner::str($this->param_name) . '::class';
    }
}
