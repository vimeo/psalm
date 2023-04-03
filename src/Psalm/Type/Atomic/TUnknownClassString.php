<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `class-string` type, used to describe a string representing a valid PHP class.
 * The parent type from which the classes descend may or may not be specified in the constructor.
 *
 * @psalm-immutable
 */
final class TUnknownClassString extends TClassString
{
    public ?TObject $as_unknown_type;

    public function __construct(
        ?TObject $as_unknown_type,
        bool $is_loaded = false,
        bool $from_docblock = false
    ) {
        parent::__construct(
            'object',
            null,
            $is_loaded,
            false,
            false,
            $from_docblock,
        );
        $this->as_unknown_type = $as_unknown_type;
    }
}
