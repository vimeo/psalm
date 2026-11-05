<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use PhpParser\Node\Stmt;
use Psalm\Internal\Provider\ParserCacheProvider;

final class CountingParserCacheProvider extends ParserCacheProvider
{
    /** @var array<string, int> */
    public array $loads = [];

    /**
     * @return list<Stmt>|null
     */
    #[Override]
    public function loadStatementsFromCache(string $file_path, ?string $file_content_hash): ?array
    {
        $this->loads[$file_path] = ($this->loads[$file_path] ?? 0) + 1;

        return parent::loadStatementsFromCache($file_path, $file_content_hash);
    }
}
