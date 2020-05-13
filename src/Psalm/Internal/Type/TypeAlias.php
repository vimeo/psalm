<?php
namespace Psalm\Internal\Type;

class TypeAlias
{
    /**
     * @var list<array{0: string, 1: int}>|null
     */
    public $replacement_tokens = null;

    /**
     * @param list<array{0: string, 1: int}>|null $replacement_tokens
     */
    public function __construct(?array $replacement_tokens)
    {
        $this->replacement_tokens = $replacement_tokens;
    }
}
