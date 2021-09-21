<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;

class DomNodeAppendChild implements \Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['DomNode'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union
    {
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();

        if ($method_name_lowercase !== 'appendchild') {
            return null;
        }

        if (!$source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        if (($first_arg_type = $source->node_data->getType($call_args[0]->value))
            && $first_arg_type->hasObjectType()
        ) {
            return clone $first_arg_type;
        }

        return null;
    }
}
