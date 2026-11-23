<?php

declare(strict_types=1);

namespace Psalm\Tests\Overlay;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Overlay\Pass;
use Psalm\Internal\Overlay\Strategy;
use Psalm\Tests\TestCase;

use function str_replace;
use function substr;

/**
 * What an overlay changes for other files, from the scan storages before and after it
 */
final class StorageFingerprintTest extends TestCase
{
    private const ORIGINAL = <<<'PHP'
        <?php
        namespace App;

        class B
        {
            public const Y = 1;
        }

        class A
        {
            public const X = 1;

            public function foo(int $i = B::Y): int
            {
                $f = fn(int $j): int => $j;

                return $f($i);
            }
        }
        PHP;

    /**
     * @param list<string> $members
     */
    #[DataProvider('provideOverlays')]
    public function testClassification(string $overlay, string $strategy, array $members = []): void
    {
        $file_path = self::$src_dir_path . 'A.php';
        $this->addFile($file_path, self::ORIGINAL);

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanner->addFileToDeepScan($file_path);
        $codebase->scanFilesWithoutPopulating();

        $signatures = Pass::applyOverlays($this->project_analyzer, [$file_path => $overlay]);

        $this->assertSame([$strategy, $members], Pass::classify($signatures, []));
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2?: list<string>}>
     * @psalm-pure
     */
    public static function provideOverlays(): iterable
    {
        yield 'positions only' => [
            "<?php\n// a comment that moves everything\n\n" . substr(self::ORIGINAL, 6),
            Strategy::BODY,
        ];

        yield 'a method body' => [
            str_replace('return $f($i);', 'return $f($i + 1);', self::ORIGINAL),
            Strategy::BODY,
        ];

        yield 'a closure moved inside a body' => [
            str_replace('$f = fn', "\n\n        \$f = fn", self::ORIGINAL),
            Strategy::BODY,
        ];

        yield 'the visibility of a method' => [
            str_replace('public function foo', 'protected function foo', self::ORIGINAL),
            Strategy::VISIBILITY,
            ['func app\a::foo', 'return app\a::foo'],
        ];

        yield 'a return type' => [
            str_replace('): int' . "\n", '): ?int' . "\n", self::ORIGINAL),
            Strategy::SIGNATURE,
        ];

        yield 'a parameter default naming a constant of another class' => [
            str_replace('int $i = B::Y', 'int $i = 2', self::ORIGINAL),
            Strategy::SIGNATURE,
        ];

        yield 'an added method' => [
            str_replace('public const X = 1;', "public const X = 1;\n    public function bar(): void {}", self::ORIGINAL),
            Strategy::DECLARATIONS,
        ];

        yield 'a removed class' => [
            str_replace("class B\n{\n    public const Y = 1;\n}\n", '', self::ORIGINAL),
            Strategy::DECLARATIONS,
        ];

        yield 'the value of a class constant' => [
            str_replace('public const X = 1;', 'public const X = 2;', self::ORIGINAL),
            Strategy::FRESH,
        ];
    }

    public function testFunctionOfAFileTheScanRegisteredGloballyNeedsAFreshProcess(): void
    {
        $file_path = self::$src_dir_path . 'functions.php';
        $this->addFile($file_path, '<?php function helper(int $i): int { return $i; }');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanner->addFileToDeepScan($file_path);
        $codebase->scanFilesWithoutPopulating();

        $signatures = Pass::applyOverlays(
            $this->project_analyzer,
            [$file_path => '<?php function helper(int $i): string { return (string) $i; }'],
        );

        $this->assertSame([Strategy::SIGNATURE, []], Pass::classify($signatures, []));
        $this->assertSame([Strategy::FRESH, []], Pass::classify($signatures, [$file_path]));
    }

    public function testAClassNamedLikeAnAnonymousClassIsNotAPosition(): void
    {
        $file_path = self::$src_dir_path . 'A.php';
        $original = '<?php namespace App; class A { public function f(foo_php_1_2 $x): void {} }';
        $this->addFile($file_path, $original);

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanner->addFileToDeepScan($file_path);
        $codebase->scanFilesWithoutPopulating();

        $signatures = Pass::applyOverlays(
            $this->project_analyzer,
            [$file_path => str_replace('foo_php_1_2', 'foo_php_1_3', $original)],
        );

        $this->assertSame([Strategy::SIGNATURE, []], Pass::classify($signatures, []));
    }
}
