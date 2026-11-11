<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\CountingParserCacheProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_exists;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class MutationStatementsCacheTest extends TestCase
{
    private string $cache_dir = '';

    private CountingParserCacheProvider $parser_cache;

    #[Override]
    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->cache_dir !== '' && file_exists($this->cache_dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            // Best effort: ProjectAnalyzer::$instance keeps this test's Cache alive, and
            // Cache holds its lock file open, which Windows refuses to delete.
            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }

            @rmdir($this->cache_dir);
        }
    }

    #[DataProvider('provideModes')]
    public function testInlinedMethodStatementsAreFetchedOnceAfterTheFirstRetainedFetch(
        bool $array_cache,
        bool $server_mode,
        bool $language_server,
        int $expected_fetches,
    ): void {
        $file_path = 'somefile.php';

        $this->useParserCache($array_cache);
        $this->project_analyzer->getCodebase()->server_mode = $server_mode;
        $this->project_analyzer->getCodebase()->language_server = $language_server;
        $this->addFile(
            $file_path,
            '<?php
                class UserViewData {
                    /** @var string|null */
                    public $name;
                }

                class Controller {
                    public UserViewData $user_viewdata;

                    public ?string $title = null;

                    public function __construct() {
                        $this->user_viewdata = new UserViewData();
                    }

                    public function setUser(): void
                    {
                        $this->user_viewdata->name = "bob";
                    }
                }

                class FooController extends Controller {
                    public function barBar(): void {
                        $this->setUser();

                        if (rand(0, 1)) {
                            $this->title = "hello";
                        }
                    }
                }',
        );

        new FileAnalyzer($this->project_analyzer, $file_path, $file_path);
        $this->project_analyzer->getCodebase()->scanFiles();

        $fetches_before = $this->parser_cache->loads[$file_path] ?? 0;

        for ($i = 0; $i < 5; $i++) {
            $method_context = new Context();
            $method_context->collect_mutations = true;
            $this->project_analyzer->getMethodMutations(
                new MethodIdentifier('FooController', 'barbar'),
                $method_context,
                $file_path,
                $file_path,
            );

            // inlining the callee gives the same result with or without the cache
            $this->assertSame('UserViewData', (string) $method_context->vars_in_scope['$this->user_viewdata']);
            $this->assertSame('string', (string) $method_context->vars_in_scope['$this->user_viewdata->name']);
            $this->assertTrue($method_context->vars_possibly_in_scope['$this->title']);
        }

        $this->assertSame($expected_fetches, ($this->parser_cache->loads[$file_path] ?? 0) - $fetches_before);
    }

    public function testReloadingFilesDropsTheKeptStatements(): void
    {
        $file_path = 'somefile.php';

        $this->useParserCache(true);
        $this->addFile(
            $file_path,
            '<?php
                class Controller {
                    public ?string $title = null;

                    public function setTitle(): void
                    {
                        $this->title = "hello";
                    }
                }

                class FooController extends Controller {
                    public function barBar(): void {
                        $this->setTitle();
                    }
                }',
        );

        new FileAnalyzer($this->project_analyzer, $file_path, $file_path);
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanFiles();

        $fetches = [];

        for ($run = 0; $run < 2; $run++) {
            $fetches_before = $this->parser_cache->loads[$file_path] ?? 0;

            for ($i = 0; $i < 5; $i++) {
                $method_context = new Context();
                $method_context->collect_mutations = true;
                $this->project_analyzer->getMethodMutations(
                    new MethodIdentifier('FooController', 'barbar'),
                    $method_context,
                    $file_path,
                    $file_path,
                );
            }

            $fetches[] = ($this->parser_cache->loads[$file_path] ?? 0) - $fetches_before;

            $codebase->reloadFiles($this->project_analyzer, []);
        }

        // the reload forgets the file, so it is again kept only from its second fetch
        $this->assertSame([2, 2], $fetches);
    }

    /**
     * Each call fetches the file twice: once for barBar() and once more when inlining setUser() into it.
     * Only the first two fetches reach the parser cache, the first one is not retained. In server mode and in the
     * language server files can change, so every fetch does.
     *
     * @return iterable<string, array{bool, bool, bool, int}>
     * @psalm-pure
     */
    public static function provideModes(): iterable
    {
        yield 'arrayCache off' => [false, false, false, 2];
        yield 'arrayCache on' => [true, false, false, 2];
        yield 'server mode' => [true, true, false, 10];
        yield 'language server' => [true, false, true, 10];
    }

    private function useParserCache(bool $array_cache): void
    {
        $this->cache_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-mutation-stmts-', true);

        $this->testConfig->cache_directory = $this->cache_dir;
        $this->testConfig->array_cache = $array_cache;

        RuntimeCaches::clearAll();

        $this->parser_cache = new CountingParserCacheProvider($this->testConfig, '');
        $this->project_analyzer = new ProjectAnalyzer(
            $this->testConfig,
            new Providers($this->file_provider, $this->parser_cache),
        );
        $this->project_analyzer->setPhpVersion('7.4', 'tests');
    }
}
