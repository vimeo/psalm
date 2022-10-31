<?php

namespace Psalm\Internal;

use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

/**
 * @internal
 */
class ReferenceConstraint
{
    /** @var Union|null */
    public $type;

    public function __construct(?Union $type = null)
    {
        if ($type) {
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
