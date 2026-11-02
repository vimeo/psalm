<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Config;
use Psalm\Internal\Cache;

use function array_map;
use function dirname;
use function filemtime;

use const DIRECTORY_SEPARATOR;

/** @internal */
final class ParserCacheProvider
{
    private const PARSER_CACHE_DIRECTORY = 'php-parser';

    /**
     * The statements of a file, and the raw message and attributes of each error the parser recovered from, which
     * are stored rather than the exceptions, as those carry their stack trace
     *
     * @var Cache<array{list<PhpParser\Node\Stmt>, list<array{string, array<string, mixed>}>}>
     */
    private readonly Cache $stmtCache;

    public function __construct(Config $config, string $composerLock, bool $persistent = true)
    {
        $deps = [
            $composerLock,
            PHP_PARSER_VERSION,
            (string) filemtime(__DIR__.DIRECTORY_SEPARATOR.'StatementsProvider.php'),
            // decides which compile errors are cached with the statements
            (string) filemtime(
                dirname(__DIR__).DIRECTORY_SEPARATOR.'PhpVisitor'.DIRECTORY_SEPARATOR.'PartialApplicationVisitor.php',
            ),
        ];

        $this->stmtCache = new Cache($config, self::PARSER_CACHE_DIRECTORY, $deps, $persistent);
    }

    public function consolidate(): void
    {
        $this->stmtCache->consolidate();
    }

    /**
     * @return array{list<PhpParser\Node\Stmt>, list<PhpParser\Error>}|null
     */
    public function loadStatementsFromCache(
        string $file_path,
        ?string $file_content_hash,
    ): ?array {
        /** @var array{list<PhpParser\Node\Stmt>, list<array{string, array<string, mixed>}>}|null $item */
        $item = $this->stmtCache->getItem($file_path, $file_content_hash);

        if ($item === null) {
            return null;
        }

        return [
            $item[0],
            array_map(
                static fn(array $error): PhpParser\Error => new PhpParser\Error($error[0], $error[1]),
                $item[1],
            ),
        ];
    }

    public function getHash(string $file_path): ?string
    {
        return $this->stmtCache->getHash($file_path);
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     * @param  list<PhpParser\Error>            $parse_errors
     */
    public function saveStatementsToCache(
        string $file_path,
        string $file_content_hash,
        array $stmts,
        array $parse_errors,
    ): void {
        $this->stmtCache->saveItem(
            $file_path,
            [
                $stmts,
                array_map(
                    static fn(PhpParser\Error $error): array => [$error->getRawMessage(), $error->getAttributes()],
                    $parse_errors,
                ),
            ],
            $file_content_hash,
        );
    }
}
