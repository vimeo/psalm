<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;

/**
 * @psalm-immutable
 */
final class LegacySyntaxManipulationTest extends FileManipulationTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): array
    {
        return [
            'replaceEmptyArrayTypes' => [
                'input' => '<?php
                    /**
                     * @param array<empty, empty> $a
                     * @param array<string, list<empty>> $b
                     * @param array<
                     *     empty,
                     *     empty
                     * > $c
                     * @return list<empty>
                     */
                    function f(array $a, array $b, array $c): array {
                        /** @var list<empty> $d */
                        $d = [];
                        return $d;
                    }',
                'output' => '<?php
                    /**
                     * @param array{} $a
                     * @param array<string, array{}> $b
                     * @param array{} $c
                     * @return array{}
                     */
                    function f(array $a, array $b, array $c): array {
                        /** @var array{} $d */
                        $d = [];
                        return $d;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['LegacySyntax'],
                'safe_types' => true,
            ],
            'keepEmptyInAssertion' => [
                'input' => '<?php
                    /**
                     * @param mixed $value
                     * @psalm-assert list<empty> $value
                     */
                    function assertNone($value): void {}',
                'output' => '<?php
                    /**
                     * @param mixed $value
                     * @psalm-assert list<empty> $value
                     */
                    function assertNone($value): void {}',
                'php_version' => '7.4',
                'issues_to_fix' => ['LegacySyntax'],
                'safe_types' => true,
            ],
        ];
    }
}
