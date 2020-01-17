<?php
namespace Psalm\Type\Atomic;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

class TOpenResource extends TResource
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'open-resource';
    }
}
