<?php
namespace Psalm\Tests;

class IssueSuppressionTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'undefined-class' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress UndefinedClass
                         * @psalm-suppress MixedMethodCall
                         * @psalm-suppress MissingReturnType
                         */
                        public function b() {
                            B::fooFoo()->barBar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
                        }
                    }'
            ],
            'exclude-issue' => [
                '<?php
                    fooFoo();',
                'assertions' => [],
                'error_levels' => ['UndefinedFunction']
            ]
        ];
    }
}
