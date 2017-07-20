<?php
namespace Psalm\Storage;

class FileStorage
{
    /**
     * @var array<int, string>
     */
    public $classes_in_file = [];

    public $file_path;

    /**
     * @var array<FunctionLikeStorage>
     */
    public $functions = [];

    /** @var array<string, string> */
    public $declaring_function_ids = [];

    /** @var array<string, string> */
    public $included_file_paths = [];

    /** @var bool */
    public $populated = false;
}
