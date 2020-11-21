<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Internal\DataFlow\DataFlowNode;

class StrTrReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'strtr',
        ];
    }

    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        $code_base = $statements_source->getCodebase($statements_source, $function_id);
        $function_like_storage = $code_base->getFunctionLikeStorage($statements_source, $function_id);
        
        $type = Type::getString();

        if ($statements_source->data_flow_graph && !\in_array('TaintedInput', $statements_source->getSuppressedIssues())) {
            $function_return_sink = DataFlowNode::getForMethodReturn(
                $function_id,
                $function_id,
                null,
                $code_location
            );
            
            $statements_source->data_flow_graph->addNode($function_return_sink);
            foreach($call_args as $i => $_) {
                $function_param_sink = DataFlowNode::getForMethodArgument(
                    $function_id,
                    $function_id,
                    $i,
                    null,
                    $code_location
                );

                $statements_source->data_flow_graph->addNode($function_param_sink);

                $statements_source->data_flow_graph->addPath(
                    $function_param_sink,
                    $function_return_sink,
                    'arg'
                );
            }

            $type->parent_nodes = [$function_return_sink->id => $function_return_sink];
        }

        return $type;
    }
}
