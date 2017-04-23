<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use Psalm\Checker\FileChecker;
use Psalm\Context;

class VariadicTest extends TestCase
{
    /**
     * @dataProvider providerTestValidVariadic
     * @param string $code
     * @return void
     */
    public function testVariadic($code)
    {
        $stmts = self::$parser->parse($code);

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     * @return                   void
     */
    public function testVariadicArrayBadParam()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param array<int, int> $a_list
         * @return void
         */
        function f(int ...$a_list) {
        }
        f(1, 2, "3");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
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
                 * @return array<mixed>
                 */
                function f($req, $opt = null, ...$params) {
                    return $params;
                }
        
                f(1);
                f(1, 2);
                f(1, 2, 3);
                f(1, 2, 3, 4);
                f(1, 2, 3, 4, 5);'
            ],
            'variadic-array' => [
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
                    }'
            ]
        ];
    }
}
