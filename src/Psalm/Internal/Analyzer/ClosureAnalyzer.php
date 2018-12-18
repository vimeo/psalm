<?php
namespace Psalm\Internal\Analyzer;

/**
 * @internal
 */
class ClosureAnalyzer extends FunctionLikeAnalyzer
{
    public function __construct(\PhpParser\Node\Expr\Closure $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $function_id = $source->getFilePath()
            . ':' . $function->getLine()
            . ':' . (int)$function->getAttribute('startFilePos')
            . ':-:closure';

        $storage = $codebase->getClosureStorage($source->getFilePath(), $function_id);

        parent::__construct($function, $source, $storage);
    }
}
