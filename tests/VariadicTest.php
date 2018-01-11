<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class VariadicTest extends TestCase
{
    /**
     * @dataProvider providerTestValidVariadic
     *
     * @param string $code
     *
     * @return void
     */
    public function testVariadic($code)
    {
        $this->addFile(
            'somefile.php',
            $code
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     *
     * @return                   void
     */
    public function testVariadicArrayBadParam()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<int, int> $a_list
                 * @return void
                 */
                function f(int ...$a_list) {
                }
                f(1, 2, "3");
                '
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return array
     */
    public function providerTestValidVariadic()
    {
        return [
            'variadic' => [
                '<?php
                /**
                 * @param mixed $req
                 * @param mixed $opt
                 * @param array<int, mixed> $params
                 * @return array<mixed>
                 */
                function f($req, $opt = null, ...$params) {
                    return $params;
                }

                f(1);
                f(1, 2);
                f(1, 2, 3);
                f(1, 2, 3, 4);
                f(1, 2, 3, 4, 5);',
            ],
            'funcNumArgsVariadic' => [
                '<?php
                    function test(): array {
                        return func_get_args();
                    }
                    var_export(test(2));',
            ],
            'variadicArray' => [
                '<?php
                    /**
                     * @param array<int, int> $a_list
                     * @return array<int, int>
                     */
                    function f(int ...$a_list) {
                        return array_map(
                            /**
                             * @return int
                             */
                            function (int $a) {
                                return $a + 1;
                            },
                            $a_list
                        );
                    }

                    f(1);
                    f(1, 2);
                    f(1, 2, 3);

                    /**
                     * @param string ...$a_list
                     * @return void
                     */
                    function g(string ...$a_list) {
                    }',
            ],
        ];
    }
}
