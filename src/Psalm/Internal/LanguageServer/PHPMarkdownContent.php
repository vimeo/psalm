<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\MarkupContent;
use LanguageServerProtocol\MarkupKind;

class PHPMarkdownContent extends MarkupContent
{
    public function __construct(string $title, string $code, ?string $description = '')
    {
        parent::__construct(
            MarkupKind::MARKDOWN,
            "**$title**\n\n$description\n```php\n<?php\n$code\n```"
        );
    }
}