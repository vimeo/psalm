<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use PhpParser\Node\Stmt;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\ParserCacheProvider;

use function serialize;
use function str_ends_with;
use function unserialize;

final class FakeParserCacheProvider extends ParserCacheProvider
{
    /**
     * The contents and serialized statements of each stub file, which every test case parses again, by the analysed
     * PHP version they were parsed for
     *
     * @var array<string, array{string, string}>
     */
    private static array $stub_statements = [];

    public function __construct()
    {
    }

    #[Override]
    public function loadStatementsFromCache(string $file_path, ?string $file_content_hash): ?array
    {
        $key = self::getKey($file_path);
        if (!isset(self::$stub_statements[$key]) || self::$stub_statements[$key][0] !== $file_content_hash) {
            return null;
        }

        /** @var list<Stmt> */
        return unserialize(self::$stub_statements[$key][1]);
    }

    #[Override]
    public function saveStatementsToCache(string $file_path, string $file_content_hash, array $stmts): void
    {
        if (str_ends_with($file_path, '.phpstub')) {
            self::$stub_statements[self::getKey($file_path)] = [$file_content_hash, serialize($stmts)];
        }
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getHash(string $file_path): ?string
    {
        return null;
    }

    /**
     * The parser follows the analysed PHP version (StatementsProvider::parseStatements()), so the statements do too
     */
    private static function getKey(string $file_path): string
    {
        return ProjectAnalyzer::getInstance()->getCodebase()->analysis_php_version_id . ':' . $file_path;
    }
}
