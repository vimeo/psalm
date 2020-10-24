<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type\Union;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;

class AttributeArg
{
    /**
     * @var ?string
     */
    public $name;

    /**
     * @var Union|UnresolvedConstantComponent
     */
    public $type;

    /**
     * @var CodeLocation
     */
    public $location;

    /**
     * @param Union|UnresolvedConstantComponent  $type
     */
    public function __construct(
        ?string $name,
        $type,
        CodeLocation $location
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->location = $location;
    }
}
