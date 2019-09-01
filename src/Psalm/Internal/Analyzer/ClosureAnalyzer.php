<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;

/**
 * @internal
 */
class ClosureAnalyzer extends FunctionLikeAnalyzer
{
    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $function
     * @param SourceAnalyzer               $source   [description]
     */
    public function __construct(PhpParser\Node\FunctionLike $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $function_id = \strtolower($source->getFilePath())
            . ':' . $function->getLine()
            . ':' . (int)$function->getAttribute('startFilePos')
            . ':-:closure';

        $storage = $codebase->getClosureStorage($source->getFilePath(), $function_id);

        parent::__construct($function, $source, $storage);
    }

    public function getTemplateTypeMap()
    {
        return $this->source->getTemplateTypeMap();
    }
}
