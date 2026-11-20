<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for symfony/yaml, which Psalm loads because the code below declares classes of the
 * package, as its sources do
 */
final class SymfonyYamlLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Symfony\Component\Yaml;

            class Yaml {
                public const PARSE_EXCEPTION_ON_INVALID_TYPE = 2;
                public const PARSE_OBJECT = 4;
                public const PARSE_OBJECT_FOR_MAP = 8;
                public const PARSE_DATETIME = 32;
                public const PARSE_CONSTANT = 256;

                public static function parse(string $input, int $flags = 0): mixed {
                    return null;
                }
            }

            class Parser {
                public const DEFAULT_MAX_NESTING_LEVEL = 128;
                public const DEFAULT_MAX_ALIASES_FOR_COLLECTIONS = 128;

                public function parse(string $value, int $flags = 0): mixed {
                    return null;
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
            'objects' => [
                '<?php
                    use Symfony\Component\Yaml\Parser;
                    use Symfony\Component\Yaml\Yaml;

                    function load(int $flags): void {
                        $yaml = (string) $_GET["yaml"];
                        (new Parser())->parse($yaml, Yaml::PARSE_OBJECT | Yaml::PARSE_OBJECT_FOR_MAP);
                        Yaml::parse($yaml, Yaml::PARSE_OBJECT);
                        Yaml::parse($yaml, $flags);
                        Yaml::parse($yaml);
                        Yaml::parse($yaml, Yaml::PARSE_CONSTANT | Yaml::PARSE_DATETIME);
                    }',
                [
                    'TaintedUnserialize on line 7',
                    'TaintedUnserialize on line 8',
                    'TaintedUnserialize on line 9',
                ],
            ],
        ];
    }
}
