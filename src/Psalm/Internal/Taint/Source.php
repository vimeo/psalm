<?php

namespace Psalm\Internal\Taint;

use Psalm\CodeLocation;

class Source extends Taintable
{
    /** @var array<int, Source> */
    public $parents = [];
}
