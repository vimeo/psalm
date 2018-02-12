<?php
namespace Psalm\Storage;

class FileStorage
{
    /**
     * @var array<string, string>
     */
    public $classes_in_file = [];

    /** @var string */
    public $file_path;

    /**
     * @var array<string, FunctionLikeStorage>
     */
    public $functions = [];

    /** @var array<string, string> */
    public $declaring_function_ids = [];

    /**
     * @var array<string, \Psalm\Type\Union>
     */
    public $constants = [];

    /** @var array<string, string> */
    public $declaring_constants = [];

    /** @var array<string, string> */
    public $included_file_paths = [];

    /** @var bool */
    public $populated = false;
}
