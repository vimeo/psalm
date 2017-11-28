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
                    '$a' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'nullCoalesce' => [
                '<?php
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'nullCoalesceWithGoodVariable' => [
                '<?php
                    $b = rand(0, 10) > 5 ? "hello" : null;
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'string|null',
                ],
            ],
            'issetKeyedOffset' => [
                '<?php
                    if (!isset($foo["a"])) {
                        $foo["a"] = "hello";
                    }',
                'assertions' => [
                    '$foo[\'a\']' => 'mixed',
                ],
                'error_levels' => [],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray(),
                ],
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
                    '$foo' => \Psalm\Type::getArray(),
                ],
            ],
            'nullCoalesceKeyedOffset' => [
                '<?php
                    $foo["a"] = $foo["a"] ?? "hello";',
                'assertions' => [
                    '$foo[\'a\']' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray(),
                ],
            ],
            'noRedundantConditionOnMixed' => [
                '<?php
                    function testarray(array $data) : void {
                        foreach ($data as $item) {
                            if (isset($item["a"]) && isset($item["b"]) && isset($item["b"]["c"])) {
                                echo "Found\n";
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
        ];
    }
}
