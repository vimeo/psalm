<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Config;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Union;

/**
 * @internal
 */
final class PdoStatementReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return ['PDOStatement'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $config = Config::getInstance();
        $method_name_lowercase = $event->getMethodNameLowercase();

        if (!$config->php_extensions["pdo"]) {
            return null;
        }

        if ($method_name_lowercase === 'fetch') {
            return self::handleFetch($event);
        }

        if ($method_name_lowercase === 'fetchall') {
            return self::handleFetchAll($event);
        }

        return null;
    }

    private static function handleFetch(MethodReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $fetch_mode = 0;
        
        foreach ($call_args as $call_arg) {
            $arg_name = $call_arg->name;
            if (!isset($arg_name) || $arg_name->name === "mode") {
                $arg_type = $source->getNodeTypeProvider()->getType($call_arg->value);
                if (isset($arg_type) && $arg_type->isSingleIntLiteral()) {
                    $fetch_mode = $arg_type->getSingleIntLiteral()->value;
                }
                break;
            }
        }
        return match ($fetch_mode) {
            2 => new Union([
                new TArray([
                    Type::getString(),
                    new Union([
                        new TScalar(),
                        new TNull(),
                    ]),
                ]),
                new TFalse(),
            ]),
            4 => new Union([
                new TArray([
                    Type::getArrayKey(),
                    new Union([
                        new TScalar(),
                        new TNull(),
                    ]),
                ]),
                new TFalse(),
            ]),
            6 => Type::getBool(),
            7 => new Union([
                new TScalar(),
                new TNull(),
                new TFalse(),
            ]),
            8 => new Union([
                new TObject(),
                new TFalse(),
            ]),
            1 => new Union([
                new TObject(),
                new TFalse(),
            ]),
            11 => new Union([
                new TArray([
                    Type::getString(),
                    new Union([
                        new TScalar(),
                        new TNull(),
                        Type::getListAtomic(
                            new Union([
                                new TScalar(),
                                new TNull(),
                            ]),
                        ),
                    ]),
                ]),
                new TFalse(),
            ]),
            12 => new Union([
                new TArray([
                    Type::getArrayKey(),
                    new Union([
                        new TScalar(),
                        new TNull(),
                    ]),
                ]),
            ]),
            3 => new Union([
                Type::getListAtomic(
                    new Union([
                        new TScalar(),
                        new TNull(),
                    ]),
                ),
                new TFalse(),
            ]),
            5 => new Union([
                new TNamedObject('stdClass'),
                new TFalse(),
            ]),
            default => null,
        };
    }

    private static function handleFetchAll(MethodReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $fetch_mode = 0;

        if (isset($call_args[0])
            && ($first_arg_type = $source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $first_arg_type->isSingleIntLiteral()
        ) {
            $fetch_mode = $first_arg_type->getSingleIntLiteral()->value;
        }

        $fetch_class_name = null;

        if (isset($call_args[1])
            && ($second_arg_type = $source->getNodeTypeProvider()->getType($call_args[1]->value))
            && $second_arg_type->isSingleStringLiteral()
        ) {
            $fetch_class_name = $second_arg_type->getSingleStringLiteral()->value;
        }
        return match ($fetch_mode) {
            2 => new Union([
                Type::getListAtomic(
                    new Union([
                        new TArray([
                            Type::getString(),
                            new Union([
                                new TScalar(),
                                new TNull(),
                            ]),
                        ]),
                    ]),
                ),
            ]),
            4 => new Union([
                Type::getListAtomic(
                    new Union([
                        new TArray([
                            Type::getArrayKey(),
                            new Union([
                                new TScalar(),
                                new TNull(),
                            ]),
                        ]),
                    ]),
                ),
            ]),
            6 => new Union([
                Type::getListAtomic(
                    Type::getBool(),
                ),
            ]),
            7 => new Union([
                Type::getListAtomic(
                    new Union([
                        new TScalar(),
                        new TNull(),
                    ]),
                ),
            ]),
            8 => new Union([
                Type::getListAtomic(
                    new Union([
                        $fetch_class_name ? new TNamedObject($fetch_class_name) : new TObject(),
                    ]),
                ),
            ]),
            11 => new Union([
                Type::getListAtomic(
                    new Union([
                        new TArray([
                            Type::getString(),
                            new Union([
                                new TScalar(),
                                new TNull(),
                                Type::getListAtomic(
                                    new Union([
                                        new TScalar(),
                                        new TNull(),
                                    ]),
                                ),
                            ]),
                        ]),
                    ]),
                ),
            ]),
            12 => new Union([
                new TArray([
                    Type::getArrayKey(),
                    new Union([
                        new TScalar(),
                        new TNull(),
                    ]),
                ]),
            ]),
            3 => new Union([
                Type::getListAtomic(
                    new Union([
                        Type::getListAtomic(
                            new Union([
                                new TScalar(),
                                new TNull(),
                            ]),
                        ),
                    ]),
                ),
            ]),
            5 => new Union([
                Type::getListAtomic(
                    new Union([
                        new TNamedObject('stdClass'),
                    ]),
                ),
            ]),
            default => null,
        };
    }
}
