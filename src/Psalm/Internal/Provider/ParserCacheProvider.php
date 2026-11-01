<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Config;
use Psalm\Internal\Cache;

use function filemtime;

use const DIRECTORY_SEPARATOR;

/** @internal */
final class ParserCacheProvider
{
    private const PARSER_CACHE_DIRECTORY = 'php-parser';
    private const PARSE_ERROR_CACHE_DIRECTORY = 'php-parser-errors';

    /** @var Cache<list<PhpParser\Node\Stmt>> */
    private readonly Cache $stmtCache;

    /** @var Cache<list<array{string, array<string, mixed>}>>|null */
    private ?Cache $parseErrorCache = null;

    public function __construct(Config $config, string $composerLock, bool $persistent = true)
    {
        $deps = [
            $composerLock,
            PHP_PARSER_VERSION,
            (string) filemtime(__DIR__.DIRECTORY_SEPARATOR.'StatementsProvider.php'),
        ];

        $this->stmtCache = new Cache($config, self::PARSER_CACHE_DIRECTORY, $deps, $persistent);
        $this->parseErrorCache = new Cache($config, self::PARSE_ERROR_CACHE_DIRECTORY, $deps, $persistent);
    }

    public function consolidate(): void
    {
        $this->stmtCache->consolidate();
        $this->parseErrorCache?->consolidate();
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache(
        string $file_path,
        ?string $file_content_hash,
    ): ?array {
        return $this->stmtCache->getItem($file_path, $file_content_hash);
    }

    public function getHash(string $file_path): ?string
    {
        return $this->stmtCache->getHash($file_path);
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

    /**
     * @return list<array{string, array<string, mixed>}>|null the raw message and attributes of each parse error
     */
    public function loadParseErrorsFromCache(string $file_path, string $file_content_hash): ?array
    {
        return $this->parseErrorCache?->getItem($file_path, $file_content_hash);
    }

    /**
     * @param list<array{string, array<string, mixed>}> $errors
     */
    public function saveParseErrorsToCache(string $file_path, string $file_content_hash, array $errors): void
    {
        $this->parseErrorCache?->saveItem($file_path, $errors, $file_content_hash);
    }
}
