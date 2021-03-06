<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;
use ReflectionType;

class ReflectionParameterGetType implements \Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['ReflectionParameter'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event) : ?Type\Union
    {
        if ($event->getMethodNameLowercase() !== 'gettype') {
            return null;
        }

        $variable = $event->getStmt()->var;
        if (!$variable instanceof \PhpParser\Node\Expr\Variable) {
            return null;
        }

        if (!is_string($variable->name)) {
            return null;
        }

        foreach ($event->getContext()->vars_in_scope as $name => $type) {
            if (strpos($name, '$' . $variable->name . '->hastype()') !== false) {
                if ($type->isTrue()) {
                    return Type::parseString(ReflectionType::class);
                }

                return Type::getNull();
            }
        }

        return null;
    }
}
