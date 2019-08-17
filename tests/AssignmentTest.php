<?php
namespace Psalm\Tests;

class AssignmentTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'nestedAssignment' => [
                '<?php
                    $a = $b = $c = 5;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'assignmentInByRefParams' => [
                '<?php
                    function foo(?string $s, ?string $t): void {}
                    foo($s = null, $t = null);
                    echo $s;
                    echo $t;

                    function foo2(?string &$u, ?string &$v): void {}
                    foo2($u = null, $v = null);
                    echo $u;
                    echo $v;

                    $read = [fopen(\'php://stdin\', \'rb\')];
                    $return = stream_select($read, $w = null, $e = null, 0);
                    echo $w;
                    echo $e;',
            ],
            'bitwiseAssignment' => [
                '<?php
                    $x = 0;
                    $x |= (int) (rand(0, 1) !== 2);
                    $x |= 1;
                    if ($x) {
                        echo $x;
                    }',
            ],
            'ifAssignment' => [
                '<?php
                    if ($foo = rand(0, 1)) {
                        echo $foo;
                    }',
            ],
            'explicitlyTypedMixedAssignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    /** @var mixed */
                    $b = $a;',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'mixedAssignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    $b = $a;',
                'error_message' => 'MixedAssignment',
            ],
        ];
    }
}
