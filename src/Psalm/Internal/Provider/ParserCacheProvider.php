<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Config;
use Psalm\Internal\Cache;

/** @internal */
final class ParserCacheProvider
{
    private const PARSER_CACHE_DIRECTORY = 'php-parser';
    private const FILE_CONTENTS_CACHE_DIRECTORY = 'file-caches';

    /** @var Cache<list<PhpParser\Node\Stmt>> */
    private readonly Cache $stmtCache;
    /** @var Cache<string> */
    private readonly Cache $fileCache;

    public function __construct(Config $config, string $composerLock, bool $noFile = false)
    {
        $deps = [$composerLock, PHP_PARSER_VERSION, (string) filemtime(__DIR__.DIRECTORY_SEPARATOR.'StatementsProvider.php')];

        $this->stmtCache = new Cache($config, self::PARSER_CACHE_DIRECTORY, $deps, $noFile);
        $this->fileCache = new Cache($config, self::FILE_CONTENTS_CACHE_DIRECTORY, $deps, $noFile);
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache(
        string $file_path,
        string $file_content_hash,
    ): ?array {
        return $this->stmtCache->getItem($file_path, $file_content_hash);
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     */
    public function saveStatementsToCache(
        string $file_path,
        string $file_content_hash,
        array $stmts,
    ): void {
        $this->stmtCache->saveItem($file_path, $stmts, $file_content_hash);
    }

    public function loadFileContentsFromCache(string $file_path): ?string
    {
        return $this->fileCache->getItem($file_path);
    }

    public function cacheFileContents(string $file_path, string $file_contents): void
    {
        $this->fileCache->saveItem($file_path, $file_contents);
    }
}
