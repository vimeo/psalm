<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for monolog/monolog, which Psalm loads because the code below declares classes
 * of the package, as its sources do
 */
final class MonologLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Monolog {
                enum Level: int {
                    case Debug = 100;
                }
            }

            namespace Monolog\Handler {
                abstract class AbstractProcessingHandler {}
                class StreamHandler extends AbstractProcessingHandler {}
                class SocketHandler extends AbstractProcessingHandler {}
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
            'handlerTargets' => [
                '<?php
                    $input = (string) $_GET["input"];
                    new Monolog\Handler\StreamHandler($input);
                    new Monolog\Handler\SocketHandler($input);
                    new Monolog\Handler\StreamHandler("php://stderr");',
                [
                    'TaintedFile on line 3',
                    'TaintedSSRF on line 4',
                ],
            ],
        ];
    }
}
