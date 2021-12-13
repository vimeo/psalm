<?php
namespace Psalm\Internal;

use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

/**
 * @internal
 */
class ReferenceConstraint
{
    /** @var Type\Union|null */
    public $type;

    public function __construct(?Type\Union $type = null)
    {
        if ($type) {
            $this->type = clone $type;

            if ($this->type->getLiteralStrings()) {
                $this->type->addType(new TString);
            }

            if ($this->type->getLiteralInts()) {
                $this->type->addType(new TInt);
            }

            if ($this->type->getLiteralFloats()) {
                $this->type->addType(new TFloat);
            }
        }
    }
}
