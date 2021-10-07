<?php
namespace Psalm\Tests;

class StrictConditionTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->testConfig->strict_bool_conditions = true;
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        $output = [
            'rand' => [
                '<?php
                    if (rand(0, 1) === 0) { }'
            ],
            'non-nullable bool' => [
                '<?php
                    function bar(bool $b): void
                    {
                        if ($b) { }
                    }
                '
            ],
            'non-nullable object' => [
                '<?php
                    class A {
                        function getFoo() : ?Foo {
                            return rand(0, 1) ? new Foo : null;
                        }
                    }
                    class Foo { }

                    $a = new A();
                    if ($a->getFoo() !== null) { }
                '
            ]
        ];

        if (\version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            $output['nullable bool from nullable object'] = [
                '<?php
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

        return $output;
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        $output = [
            'nullable object' => [
                '<?php
                    class A {
                        function getFoo() : ?Foo {
                            return rand(0, 1) ? new Foo : null;
                        }
                    }
                    class Foo { }

                    $a = new A();
                    if ($a->getFoo()) { }
                ',
                'error_message' => 'NonStrictBoolCondition',
            ],
            'nullable bool' => [
                '<?php
                    function bar(?bool $b): void
                    {
                        if ($b) { }
                    }
                ',
                'error_message' => 'NonStrictBoolCondition',
            ],
            'nullable bool in else if' => [
                '<?php
                    function bar(?bool $b): void
                    {
                        if (rand(0, 1) === 0) { } else if ($b) { }
                    }
                ',
                'error_message' => 'NonStrictBoolCondition',
            ],
            'nullable bool in elseif' => [
                '<?php
                    function bar(?bool $b): void
                    {
                        if (rand(0, 1) === 0) { } elseif ($b) { }
                    }
                ',
                'error_message' => 'NonStrictBoolCondition',
            ]
        ];

        if (\version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            $output['nullable bool from nullable object'] = [
                '<?php
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
                ',
                'error_message' => 'NonStrictBoolCondition',
            ];
        }

        return $output;
    }
}
