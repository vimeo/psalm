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
    public function __construct(int $start, int $end, string $insertion_text)
    {
        $this->start = $start;
        $this->end = $end;
        $this->insertion_text = $insertion_text;
    }

    public function getKey() : string
    {
        return sha1($this->start . ':' . $this->insertion_text);
    }
}
