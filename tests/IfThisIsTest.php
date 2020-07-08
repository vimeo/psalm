<?php
namespace Psalm\Tests;

class IfThisIsTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'worksAfterConvert' => [
                '<?php
                    interface I {
                        /**
                         * @return void
                         */
                        public function test();
                    }

                    class F implements I
                    {
                        /**
                         * @psalm-self-out I
                         * @return void
                         */
                        public function convert()
                        {
                        }

                        /**
                         * @psalm-if-this-is I
                         * @return void
                         */
                        public function test()
                        {
                        }
                    }

                    $f = new F();
                    $f->convert();
                    $f->test();
                '
            ]
        ];
    }

    /**
     * @return array<string, array{0: string, error_message: string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'blocksWithoutConvert' => [
                '<?php
                    interface I {
                        /**
                         * @return void
                         */
                        public function test();
                    }

                    class F implements I
                    {
                        /**
                         * @psalm-self-out I
                         * @return void
                         */
                        public function convert()
                        {
                        }

                        /**
                         * @psalm-if-this-is I
                         * @return void
                         */
                        public function test()
                        {
                        }
                    }

                    $f = new F();
                    $f->test();
                ',
                'error_message' => 'IfThisIsMismatch'
            ]
        ];
    }
}
