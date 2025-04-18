<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use JsonSerializable;
use LanguageServerProtocol\MarkupContent;
use LanguageServerProtocol\MarkupKind;
use ReturnTypeWillChange;

use function get_object_vars;

/**
 * @psalm-api
 * @internal
 */
final class PHPMarkdownContent extends MarkupContent implements JsonSerializable
{
    public function __construct(public string $code, public ?string $title = null, public ?string $description = null)
    {
        $markdown = '';
        if ($title !== null) {
            $markdown = "**$title**\n\n";
        }
        if ($description !== null) {
            $markdown = "$markdown$description\n\n";
        }
        parent::__construct(
            MarkupKind::MARKDOWN,
            "$markdown```php\n<?php declare(strict_types=1);\n$code\n```",
        );
    }

    /**
     * This is needed because VSCode Does not like nulls
     * meaning if a null is sent then this will not compute
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        $vars = get_object_vars($this);
        unset($vars['title'], $vars['description'], $vars['code']);
        return $vars;
    }
}
