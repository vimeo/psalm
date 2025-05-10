<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use PhpParser;
use Psalm\Internal\Provider\ParserCacheProvider;

final class ParserInstanceCacheProvider extends ParserCacheProvider
{
    /**
     * @var array<string, string>
     */
    private array $file_contents_cache = [];

    /**
     * @var array<string, string>
     */
    private array $file_content_hash = [];

    /**
     * @var array<string, list<PhpParser\Node\Stmt>>
     */
    private array $statements_cache = [];

    public function __construct()
    {
    }

    #[Override]
    public function loadStatementsFromCache(string $file_path, string $file_content_hash): ?array
    {
        if (isset($this->statements_cache[$file_path])
            && $this->file_content_hash[$file_path] === $file_content_hash
        ) {
            return $this->statements_cache[$file_path];
        }

        return null;
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     */
    #[Override]
    public function saveStatementsToCache(string $file_path, string $file_content_hash, array $stmts): void
    {
        $this->statements_cache[$file_path] = $stmts;
        $this->file_content_hash[$file_path] = $file_content_hash;
    }

    #[Override]
    public function loadFileContentsFromCache(string $file_path): ?string
    {
        if (isset($this->file_contents_cache[$file_path])) {
            return $this->file_contents_cache[$file_path];
        }

        return null;
    }

    #[Override]
    public function cacheFileContents(string $file_path, string $file_contents): void
    {
        $this->file_contents_cache[$file_path] = $file_contents;
    }
}
