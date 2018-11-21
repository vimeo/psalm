<?php
namespace Psalm;

class FileManipulation
{
    /** @var int */
    public $start;

    /** @var int */
    public $end;

    /** @var string */
    public $insertion_text;

    /**
     * @param int $start
     * @param int $end
     * @param string $insertion_text
     */
    public function __construct($start, $end, $insertion_text)
    {
        $this->start = $start;
        $this->end = $end;
        $this->insertion_text = $insertion_text;
    }
}
