<?php
namespace Psalm\Tests\FileManipulation;

use const PHP_VERSION;

class UndefinedVariableManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'possiblyUndefinedVariable' => [
                '<?php
                    $flag = rand(0, 1);
                    $otherflag = rand(0, 1);
                    $yetanotherflag = rand(0, 1);

                    if ($flag) {
                        if ($otherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }

                    if ($flag) {
                        if ($yetanotherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }',
                '<?php
                    $flag = rand(0, 1);
                    $otherflag = rand(0, 1);
                    $yetanotherflag = rand(0, 1);

                    $a = null;
                    if ($flag) {
                        if ($otherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }

                    if ($flag) {
                        if ($yetanotherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'twoPossiblyUndefinedVariables' => [
                '<?php
                    if (rand(0, 1)) {
                      $a = 1;
                      $b = 2;
                    }

                    echo $a;
                    echo $b;',
                '<?php
                    $a = null;
                    $b = null;
                    if (rand(0, 1)) {
                      $a = 1;
                      $b = 2;
                    }

                    echo $a;
                    echo $b;',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'possiblyUndefinedVariableInElse' => [
                '<?php
                    if (rand(0, 1)) {
                      // do nothing
                    } else {
                        $a = 5;
                    }

                    echo $a;',
                '<?php
                    $a = null;
                    if (rand(0, 1)) {
                      // do nothing
                    } else {
                        $a = 5;
                    }

                    echo $a;',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'unsetPossiblyUndefinedVariable' => [
                '<?php
                    if (rand(0, 1)) {
                      $a = "bar";
                    }
                    unset($a);',
                '<?php
                    if (rand(0, 1)) {
                      $a = "bar";
                    }
                    unset($a);',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'useUnqualifierPlugin' => [
                '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new \A\B\C\D();
                    }',
                '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new D();
                    }',
                PHP_VERSION,
                [],
                true,
            ],
        ];
    }
}
