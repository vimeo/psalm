<?php
namespace Psalm\Tests\FileManipulation;

class PureAnnotationAdditionTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'addPureAnnotationToFunction' => [
                '<?php
                    function foo(string $s): string {
                        return $s;
                    }',
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToImpureFunction' => [
                '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
        ];
    }
}
