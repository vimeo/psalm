<?php

namespace Psalm\Type;

use Psalm\Type;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;

class Atomic extends Type
{
    /** @var string */
    public $value;

    /**
     * Constructs an Atomic instance
     * @param string    $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function isIn(Union $parent)
    {
        if ($parent->isMixed()) {
            return true;
        }

        if ($parent->hasType('object') && ClassLikeChecker::classOrInterfaceExists($this->value)) {
            return true;
        }

        if ($parent->hasType('numeric') && $this->isNumericType()) {
            return true;
        }

        if ($this->value === 'false' && $parent->hasType('bool')) {
            // this is fine
            return true;
        }

        if ($parent->hasType($this->value)) {
            return true;
        }

        // last check to see if class is subclass
        if (ClassChecker::classExists($this->value)) {
            $this_is_subclass = false;

            foreach ($parent->types as $parent_type) {
                if (ClassChecker::classExtendsOrImplements($this->value, $parent_type->value)) {
                    $this_is_subclass = true;
                    break;
                }
            }

            if ($this_is_subclass) {
                return true;
            }
        }

        return false;
    }
}
