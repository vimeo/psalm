<?php

class BasePlugin extends \Psalm\Plugin
{
    public static function afterFunctionCallCheck(
        \Psalm\StatementsSource $statements_source,
        $function_id,
        array $args,
        \Psalm\CodeLocation $code_location,
        \Psalm\Context $context,
        array &$file_replacements = [],
        \Psalm\Type\Union &$return_type_candidate = null
    ) {
    }
}
