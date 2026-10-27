<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The types the stub of stubs/libraries for webmozart/assert gives the values its assertions return
 */
final class WebmozartAssertLibraryStubTypesTest extends LibraryStubsTestCase
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
     * @return non-empty-list<string>
     * @psalm-pure
     */
    #[Override]
    protected static function getReportedIssueTypePrefixes(): array
    {
        return ['Invalid'];
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'allAssertionsReturnTheListOrArrayTheyAreGiven' => [
                '<?php
                    use Webmozart\Assert\Assert;

                    /**
                     * @param list<int|null> $l
                     * @return list<int>
                     */
                    function f(array $l): array {
                        return Assert::allNotNull($l);
                    }

                    /**
                     * @param list<mixed> $l
                     * @return list<stdClass>
                     */
                    function g(array $l): array {
                        return Assert::allIsInstanceOf($l, stdClass::class);
                    }

                    /** @param array<string, mixed> $a */
                    function h(array $a): int {
                        return count(Assert::allString($a));
                    }

                    /** @param array<string, mixed> $a */
                    function i(array $a): array {
                        return Assert::allIsArray($a);
                    }',
                [],
            ],
            'allAssertionsReturnAnIterableForAnIterable' => [
                '<?php
                    use Webmozart\Assert\Assert;

                    /**
                     * @param iterable<int|null> $i
                     * @return iterable<int>
                     */
                    function f(iterable $i): iterable {
                        return Assert::allNotNull($i);
                    }

                    /** @param iterable<int|null> $i */
                    function g(iterable $i): int {
                        return count(Assert::allNotNull($i));
                    }',
                [
                    'InvalidArgument on line 14',
                ],
            ],
        ];
    }
}
