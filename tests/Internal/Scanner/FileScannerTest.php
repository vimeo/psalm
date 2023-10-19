<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Scanner;

use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionStorage;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

class FileScannerTest extends TestCase
{
    /**
     * @dataProvider providerScan
     */
    public function testScan(
        Config $config,
        string $file_contents,
        FileStorage $expected_file_storage,
    ): void {
        $file_provider = new FakeFileProvider();
        $codebase = new Codebase(
            $config,
            new Providers($file_provider),
        );

        $file_provider->registerFile('/dir/file.php', $file_contents);

        $file_scanner = new FileScanner('/dir/file.php', 'file.php', true);
        $file_storage = new FileStorage('/dir/file.php');
        $file_scanner->scan(
            $codebase,
            $file_storage,
        );

        // Reset properties that are difficult to mock
        foreach ($file_storage->functions as $function_storage) {
            $function_storage->location = null;
            $function_storage->stmt_location = null;
        }

        self::assertEquals($expected_file_storage, $file_storage);
    }

    /**
     * @return iterable<string, list{Config, string, FileStorage}>
     */
    public static function providerScan(): iterable
    {
        $config = new TestConfig();
        $config->globals['$global'] = 'GlobalClass';

        $function_storage_some_function = new FunctionStorage();
        $function_storage_some_function->cased_name = 'some_function';
        $function_storage_some_function->required_param_count = 0;
        $function_storage_some_function->global_variables = [
            '$global' => true,
        ];

        $file_storage = new FileStorage('/dir/file.php');
        $file_storage->deep_scan = true;
        $file_storage->aliases = new Aliases();
        $file_storage->functions = [
            'some_function' => $function_storage_some_function,
        ];
        $file_storage->declaring_function_ids = [
            'some_function' => '/dir/file.php',
        ];
        $file_storage->referenced_classlikes = [
            'globalclass' => 'GlobalClass',
        ];
        yield 'referenceConfiguredGlobalClass' => [
            $config,
            <<<'PHP'
            <?php

            function some_function()
            {
                global $global;
            }
            PHP,
            $file_storage,
        ];
    }
}
