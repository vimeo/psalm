<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use JsonSerializable;
use LanguageServerProtocol\MarkupContent;
use LanguageServerProtocol\MarkupKind;
use ReturnTypeWillChange;

use function get_object_vars;

class PHPMarkdownContent extends MarkupContent implements JsonSerializable
{
    /**
     * @var string
     */
    public $code;

    /**
     * @var string|null
     */
    public $title;

    /**
     * @var string|null
     */
    public $description;

    public function __construct(string $code, ?string $title = null, ?string $description = null)
    {
        $this->code = $code;
        $this->title = $title;
        $this->description = $description;

        $markdown = '';
        if ($title !== null) {
            $markdown = "**$title**\n\n";
        }
        if ($description !== null) {
            $markdown = "$markdown$description\n\n";
        }
        parent::__construct(
            MarkupKind::MARKDOWN,
            "$markdown```php\n<?php\n$code\n```"
        );
    }

    /**
     * This is needed because VSCode Does not like nulls
     * meaning if a null is sent then this will not compute
     *
     * @return mixed
     * @psalm-suppress UnusedMethod
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['title'], $vars['description'], $vars['code']);
        return $vars;
    }
}
