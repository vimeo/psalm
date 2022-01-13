<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;

class ExceptionCodeReturnTypeProvider implements \Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['Throwable'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union
    {
        $method_name_lowercase = $event->getMethodNameLowercase();
        $fqcn = $event->getCalledFqClasslikeName();

        if ($method_name_lowercase !== 'getcode') {
            return null;
        }

        if ($fqcn === 'Exception' || $fqcn === 'Throwable') {
            return null;
        }

        if (is_a($fqcn, \PDOException::class, true)) {
            return Type::parseString('string');
        }

        return Type::parseString('int');
    }
}
