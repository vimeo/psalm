<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

use function dirname;

use const DIRECTORY_SEPARATOR;

/**
 * The library stub of tests/fixtures/library_stubs, loaded because the code declares a class of it
 */
final class FixtureLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace LibraryStub;

            class Client
            {
                public function get(string $url): string
                {
                    return "";
                }

                public function head(string $url): void
                {
                }
            }';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraryStubsDir(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'library_stubs';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'stubbedSink' => [
                '<?php
                    $url = (string) $_GET["url"];
                    (new LibraryStub\Client())->get($url);
                    // a method the stub does not declare is still that of the class
                    (new LibraryStub\Client())->head($url);',
                [
                    'TaintedSSRF on line 3',
                ],
            ],
        ];
    }
}
