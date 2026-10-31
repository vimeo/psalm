<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use PhpParser\Node\Stmt;
use Psalm\Internal\Provider\ParserCacheProvider;

use function serialize;
use function str_ends_with;
use function unserialize;

final class FakeParserCacheProvider extends ParserCacheProvider
{
    /**
     * The contents and serialized statements of each stub file, which every test case parses again
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
        if (!isset(self::$stub_statements[$file_path])
            || self::$stub_statements[$file_path][0] !== $file_content_hash
        ) {
            return null;
        }

        /** @var list<Stmt> */
        return unserialize(self::$stub_statements[$file_path][1]);
    }

    #[Override]
    public function saveStatementsToCache(string $file_path, string $file_content_hash, array $stmts): void
    {
        if (str_ends_with($file_path, '.phpstub')) {
            self::$stub_statements[$file_path] = [$file_content_hash, serialize($stmts)];
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
}
