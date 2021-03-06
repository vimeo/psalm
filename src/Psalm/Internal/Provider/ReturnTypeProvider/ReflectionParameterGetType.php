<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;
use ReflectionType;
use function is_string;

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

        $stmt = $event->getStmt();
        if (!$stmt instanceof \PhpParser\Node\Expr\MethodCall) {
            return null;
        }

        if (!$stmt->var instanceof \PhpParser\Node\Expr\Variable) {
            return null;
        }

        if (!is_string($stmt->var->name)) {
            return null;
        }

        $scopedVarName = '$' . $stmt->var->name . '->hastype()';
        if (!isset($event->getContext()->vars_in_scope[$scopedVarName])) {
            return null;
        }

        $type = $event->getContext()->vars_in_scope[$scopedVarName];

        if ($type->isTrue()) {
            return new Type\Union([
                new Type\Atomic\TNamedObject(ReflectionType::class)
            ]);
        }

        return Type::getNull();
    }
}
