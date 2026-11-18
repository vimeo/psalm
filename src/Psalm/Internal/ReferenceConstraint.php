<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

/**
 * @internal
 * @psalm-immutable
 */
final class ReferenceConstraint
{
    public ?Union $type = null;

    /**
     * @param bool $widen_literals false for a declared type, whose literals limit what the reference may hold
     * @param list<string> $referenced_properties the properties (or items of properties) the variable is a reference
     *                                           to one of
     * @psalm-capabilities read-props
     * @psalm-suppress ImpureMethodCall only mutates the builder it creates
     */
    public function __construct(
        ?Union $type = null,
        bool $widen_literals = true,
        public array $referenced_properties = [],
    ) {
        if ($type && !$widen_literals) {
            $this->type = $type;
        } elseif ($type) {
            $type = $type->getBuilder();

            if ($type->getLiteralStrings()) {
                $type->addType(new TString);
            }

            if ($type->getLiteralInts()) {
                $type->addType(new TInt);
            }

            if ($type->getLiteralFloats()) {
                $type->addType(new TFloat);
            }

            $this->type = $type->freeze();
        }
    }
}
