<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use function strtolower;

/**
 * @internal
 */
class ClosureAnalyzer extends FunctionLikeAnalyzer
{
    /**
     * @var PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction
     */
    protected $function;

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

    /** @pararm PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $expr */
    public static function isExprClosureLike(PhpParser\Node\Expr $function): bool
    {
        return $function instanceof PhpParser\Node\Expr\Closure
            || $function instanceof PhpParser\Node\Expr\ArrowFunction;
    }

    public function getTemplateTypeMap()
    {
        return $this->source->getTemplateTypeMap();
    }

    /**
     * @return non-empty-lowercase-string
     */
    public function getClosureId()
    {
        return strtolower($this->getFilePath())
            . ':' . $this->function->getLine()
            . ':' . (int)$this->function->getAttribute('startFilePos')
            . ':-:closure';
    }
}
