<?php
namespace Psalm\Scope;

use Psalm\CodeLocation;

class SwitchScope
{
    /**
     * @var array<string, array<string, CodeLocation>>
     */
    public $unreferenced_vars = [];
}
