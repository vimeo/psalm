<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for webmozart/assert, which Psalm loads because the code below declares classes of
 * the package, as its sources do
 */
final class WebmozartAssertLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Webmozart\Assert {
                class InvalidArgumentException extends \InvalidArgumentException {}
                trait Mixin {}
                class Assert {
                    use Mixin;
                }
            }';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'assertionsReturnTheirValue' => [
                '<?php
                    use Webmozart\Assert\Assert;

                    echo Assert::string($_GET["a"]);
                    echo (string) Assert::nullOrString($_GET["b"]);
                    foreach (Assert::allString($_GET["c"]) as $value) {
                        echo $value;
                    }
                    echo Assert::string("safe");',
                [
                    'TaintedHtml on line 4',
                    'TaintedHtml on line 5',
                    'TaintedHtml on line 7',
                    'TaintedTextWithQuotes on line 4',
                    'TaintedTextWithQuotes on line 5',
                    'TaintedTextWithQuotes on line 7',
                ],
            ],
            'impureAssertionsAreSpecialized' => [
                '<?php
                    use Webmozart\Assert\Assert;

                    $tainted = (string) $_GET["path"];
                    Assert::fileExists($tainted);
                    echo Assert::fileExists("/tmp/file");',
                [],
            ],
        ];
    }
}
