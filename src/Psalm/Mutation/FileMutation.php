<?php
namespace Psalm\Mutation;

class FileMutation
{
    /** @var string */
    public $file_path;

    /** @var int */
    public $start;

    /** @var int */
    public $end;

    /** @var string */
    public $insertion_text;
}
