<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

use function dirname;

use const DIRECTORY_SEPARATOR;

final class MethodSignatureCallMapTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    public function testMismatchingParamShowsExactCallMapGuideType(): void
    {
        // with the extension stub disabled, the callmap type of the guide method is not overridden by a stub
        $this->project_analyzer = new ProjectAnalyzer(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <disableExtensions>
                        <extension name="dom"/>
                    </disableExtensions>
                </psalm>',
            ),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage(
            'MethodSignatureMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:49 - Argument 1 of D::loadXML'
            . ' has wrong type \'int\', expecting \'non-empty-string\' as defined by DOMDocument::loadXML',
        );

        $this->addFile(
            self::$src_dir_path . 'somefile.php',
            '<?php
                final class D extends DOMDocument {
                    public function loadXML(int $source, int $options = 0): bool { return true; }
                }',
        );
        $this->analyzeFile(self::$src_dir_path . 'somefile.php', new Context());
    }
}
