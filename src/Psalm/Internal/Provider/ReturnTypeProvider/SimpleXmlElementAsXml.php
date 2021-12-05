<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;

use function count;

class SimpleXmlElementAsXml implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['SimpleXMLElement'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union
    {
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'asxml'
            && !count($call_args)
        ) {
            return Type::parseString('string|false');
        }

        return null;
    }
}
