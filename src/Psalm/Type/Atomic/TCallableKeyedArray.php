<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Denotes an object-like array that is _also_ `callable`.
 *
 * @psalm-immutable
 */
final class TCallableKeyedArray extends TKeyedArray implements TCallableInterface
{
    protected const NAME_ARRAY = 'callable-array';
    protected const NAME_LIST = 'callable-array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param non-empty-array<string|int, Union> $properties
     * @param array{Union, Union}|null $fallback_params
     * @param array<string, bool> $class_strings
     */
    public function __construct(
        array $properties,
        ?array $class_strings = null,
        ?array $fallback_params = null,
        bool $from_docblock = false,
    ) {
        parent::__construct(
            $properties,
            $class_strings,
            $fallback_params,
            true,
            $from_docblock,
        );
    }
}
