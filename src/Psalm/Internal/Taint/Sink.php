<?php

namespace Psalm\Internal\Taint;

use Psalm\CodeLocation;

class Sink extends Taintable
{
    /** @var array<int, Sink> */
    public $children = [];
}
