<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\MarkupContent;
use LanguageServerProtocol\MarkupKind;

class PHPMarkdownContent extends MarkupContent
{
    public function __construct(string $code, ?string $title = null, ?string $description = null)
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
            "$markdown```php\n<?php\n$code\n```"
        );
    }
}