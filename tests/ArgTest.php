<?php
namespace Psalm\Tests;

class ArgTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'callMapClassOptionalArg' => [
                '<?php
                    $m = new ReflectionMethod("hello", "goodbye");
                    $m->invoke("cool");',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'possiblyInvalidArgument' => [
                '<?php
                    $foo = [
                        "a",
                        ["b"],
                    ];
            
                    $a = array_map(
                        function (string $uuid) : string {
                            return $uuid;
                        },
                        $foo[rand(0, 1)]
                    );',
                'error_message' => 'PossiblyInvalidArgument',
            ],
        ];
    }
}
