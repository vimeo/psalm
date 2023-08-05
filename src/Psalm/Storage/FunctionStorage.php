<?php

namespace Psalm\Storage;

final class FunctionStorage extends FunctionLikeStorage
{
    /** @var array<string, bool> */
    public $byref_uses = [];
}
