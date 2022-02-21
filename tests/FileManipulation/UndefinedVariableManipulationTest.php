<?php

namespace Psalm\Tests\FileManipulation;

class UndefinedVariableManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{input:string,output:string,php_version:string,issues_to_fix:string[],safe_types:bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'possiblyUndefinedVariable' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '5.6',
                'issues_to_fix' => ['PossiblyUndefinedGlobalVariable'],
                'safe_types' => true,
            ],
            'twoPossiblyUndefinedVariables' => [
                'input' => '<?php
                    if (rand(0, 1)) {
                      $a = 1;
                      $b = 2;
                    }

                    echo $a;
                    echo $b;',
                'output' => '<?php
                    $a = null;
                    $b = null;
                    if (rand(0, 1)) {
                      $a = 1;
                      $b = 2;
                    }

                    echo $a;
                    echo $b;',
                'php_version' => '5.6',
                'issues_to_fix' => ['PossiblyUndefinedGlobalVariable'],
                'safe_types' => true,
            ],
            'possiblyUndefinedVariableInElse' => [
                'input' => '<?php
                    if (rand(0, 1)) {
                      // do nothing
                    } else {
                        $a = 5;
                    }

                    echo $a;',
                'output' => '<?php
                    $a = null;
                    if (rand(0, 1)) {
                      // do nothing
                    } else {
                        $a = 5;
                    }

                    echo $a;',
                'php_version' => '5.6',
                'issues_to_fix' => ['PossiblyUndefinedGlobalVariable'],
                'safe_types' => true,
            ],
            'SKIPPED-possiblyUndefinedVariableInTry' => [
                'input' => '<?php
                    try {
                        $foo = 1;
                    } catch (Exception $_) {
                    }

                    echo $foo;
                ',
                'output' => '<?php
                    $foo = null;
                    try {
                        $foo = 1;
                    } catch (Exception $_) {
                    }

                    echo $foo;
                ',
                'php_version' => '7.3',
                'issues_to_fix' => ['PossiblyUndefinedGlobalVariable'],
                'safe_types' => true,
            ],
            'unsetPossiblyUndefinedVariable' => [
                'input' => '<?php
                    if (rand(0, 1)) {
                      $a = "bar";
                    }
                    unset($a);',
                'output' => '<?php
                    if (rand(0, 1)) {
                      $a = "bar";
                    }
                    unset($a);',
                'php_version' => '5.6',
                'issues_to_fix' => ['PossiblyUndefinedGlobalVariable'],
                'safe_types' => true,
            ],
            'useUnqualifierPlugin' => [
                'input' => '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new \A\B\C\D();
                    }',
                'output' => '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new D();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => [],
                'safe_types' => true,
            ],
        ];
    }
}
