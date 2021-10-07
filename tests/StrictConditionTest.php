<?php
namespace Psalm\Tests;

class StrictConditionTest extends TestCase
{
    /**
     * @dataProvider provideInvalidIfConditions
     */
    public function testInvalidStrictBooleanIfCondition(string $code): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('PossiblyInvalidCast');

        $this->project_analyzer->getConfig()->strict_bool_conditions = true;

        $this->addFile(
            'somefile.php',
            $code
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @dataProvider provideValidIfConditions
     */
    public function testValidStrictBooleanIfCondition(string $code): void
    {
        $this->project_analyzer->getConfig()->strict_bool_conditions = true;

        $this->addFile(
            'somefile.php',
            $code
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function provideValidIfConditions(): iterable
    {
        yield [
            '<?php
                if (rand(0, 1) === 0) { }'
        ];

        yield [
            'non-nullable bool' => '
            <?php
                function bar(bool $b): void
                {
                    if ($b) { }
                }
            '
        ];

        yield [
            'non-nullable object' => '
            <?php
                class A {
                    function getFoo() : ?Foo {
                        return rand(0, 1) ? new Foo : null;
                    }
                }
                class Foo { }

                $a = new A();
                if ($a->getFoo() !== null) { }
            '
        ];

        if (\version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            yield [
                'nullable bool from nullable object' => '
                <?php
                    final class Response
                    {
                        public function isOk(): bool
                        {
                            return true;
                        }
                    }

                    function foo(?Response $response): void
                    {
                        if ((bool) $response?->isOk()) { }
                    }
                '
            ];
        }
    }

    public function provideInvalidIfConditions(): iterable
    {
        yield [
            'nullable object' => '
            <?php
                class A {
                    function getFoo() : ?Foo {
                        return rand(0, 1) ? new Foo : null;
                    }
                }
                class Foo { }

                $a = new A();
                if ($a->getFoo()) { }
            '
        ];

        if (\version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            yield [
                'nullable bool from nullable object' => '
                <?php
                    final class Response
                    {
                        public function isOk(): bool
                        {
                            return true;
                        }
                    }

                    function foo(?Response $response): void
                    {
                        if ($response?->isOk()) { }
                    }
                '
            ];
        }

        yield [
            'nullable bool' => '
            <?php
                function bar(?bool $b): void
                {
                    if ($b) { }
                }
            '
        ];

        yield [
            'nullable bool' => '
            <?php
                function bar(?bool $b): void
                {
                    if (rand(0, 1) === 0) { } else if ($b) { }
                }
            '
        ];

        yield [
            'nullable bool' => '
            <?php
                function bar(?bool $b): void
                {
                    if (rand(0, 1) === 0) { } elseif ($b) { }
                }
            '
        ];
    }
}
