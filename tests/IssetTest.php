<?php
namespace Psalm\Tests;

class IssetTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'isset' => [
                '<?php
                    $a = isset($b) ? $b : null;',
                'assertions' => [
                    ['mixed' => '$a']
                ],
                'error_levels' => ['MixedAssignment']
            ],
            'nullCoalesce' => [
                '<?php
                    $a = $b ?? null;',
                'assertions' => [
                    ['mixed' => '$a']
                ],
                'error_levels' => ['MixedAssignment']
            ],
            'nullCoalesceWithGoodVariable' => [
                '<?php
                    $b = false;
                    $a = $b ?? null;',
                'assertions' => [
                    ['false|null' => '$a']
                ]
            ],
            'issetKeyedOffset' => [
                '<?php
                    if (!isset($foo["a"])) {
                        $foo["a"] = "hello";
                    }',
                'assertions' => [
                    ['mixed' => '$foo[\'a\']']
                ],
                'error_levels' => [],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray()
                ]
            ],
            'issetKeyedOffsetORFalse' => [
                '<?php
                    /** @return void */
                    function takesString(string $str) {}
        
                    $bar = rand(0, 1) ? ["foo" => "bar"] : false;
        
                    if (isset($bar["foo"])) {
                        takesString($bar["foo"]);
                    }',
                'assertions' => [],
                'error_levels' => [],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray()
                ]
            ],
            'nullCoalesceKeyedOffset' => [
                '<?php
                    $foo["a"] = $foo["a"] ?? "hello";',
                'assertions' => [
                    ['mixed' => '$foo[\'a\']']
                ],
                'error_levels' => ['MixedAssignment'],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray()
                ]
            ]
        ];
    }
}
