<?php
namespace Psalm\Storage;

class FileStorage
{
    /**
     * @var array<int, string>
     */
    public $classes_in_file = [];

    /**
     * @var array<FunctionLikeStorage>
     */
    public $functions = [];
}
