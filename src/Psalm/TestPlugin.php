<?php
namespace Psalm;

use Psalm\Type\Union;
use Psalm\FileManipulation\FileManipulation;
use PhpParser;
class TestPlugin extends Plugin
{
    /**
     * @param  string $function_id - the method id being checked
     * @param  PhpParser\Node\Arg[] $args
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public static function afterFunctionCallCheck(
        StatementsSource $statements_source,
        $function_id,
        array $args,
        CodeLocation $code_location,
        Context $context,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    ) {
    }
}
return new TestPlugin;
