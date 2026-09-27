<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Interner;

/**
 * Denotes an anonymous class (i.e. `new class{}`) with potential methods
 *
 * @psalm-immutable
 * @api
 */
final class TAnonymousClassInstance extends TNamedObject
{
    /**
     * @param int $value the interned name of the object
     * @param ?int $extends interned name of the parent class
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties> $extra_types
     */
    public function __construct(
        int $value,
        bool $is_static = false,
        public ?int $extends = null,
        array $extra_types = [],
    ) {
        parent::__construct($value, $is_static, false, $extra_types);
    }

    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        if ($analysis_php_version_id < 7_02_00) {
            return null;
        }
        return $this->extends === null ? 'object' : Interner::str($this->extends);
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
        return $this->extends === null ? 'object' : Interner::str($this->extends);
    }
}
