<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\StatementsProvider;

use const DIRECTORY_SEPARATOR;

final class StatementsProviderTest extends TestCase
{
    public function testStatementsOfAFileWithoutParseErrorsAreCached(): void
    {
        $file_path = $this->testConfig->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ok.php';
        $contents = '<?php function f(): void {}';
        $this->file_provider->registerFile($file_path, $contents);

        $cache = new ParserCacheProvider($this->testConfig, '', false);
        (new StatementsProvider($this->file_provider, $cache))
            ->getStatementsForFile($file_path, 80_400, false);

        $this->assertNotNull($cache->loadStatementsFromCache($file_path, $contents));
    }

    public function testStatementsOfAFileWithParseErrorsAreNotCached(): void
    {
        $file_path = $this->testConfig->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'broken.php';
        $contents = '<?php function f() { echo 1 }';
        $this->file_provider->registerFile($file_path, $contents);

        $cache = new ParserCacheProvider($this->testConfig, '', false);
        (new StatementsProvider($this->file_provider, $cache))
            ->getStatementsForFile($file_path, 80_400, false);

        $this->assertNull($cache->loadStatementsFromCache($file_path, $contents));
    }
}
