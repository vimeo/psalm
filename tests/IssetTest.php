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
            'null-coalesce' => [
                '<?php
                    $a = $b ?? null;',
                'assertions' => [
                    ['mixed' => '$a']
                ],
                'error_levels' => ['MixedAssignment']
            ],
            'null-coalesce-with-good-variable' => [
                '<?php
                    $b = false;
                    $a = $b ?? null;',
                'assertions' => [
                    ['false|null' => '$a']
                ]
            ],
            'isset-keyed-offset' => [
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
            'isset-keyed-offset-or-false' => [
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
            'null-coalesce-keyed-offset' => [
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
