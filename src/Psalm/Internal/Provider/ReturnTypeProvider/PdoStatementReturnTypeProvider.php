<?php

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

        switch ($fetch_mode) {
            case 2: // PDO::FETCH_ASSOC - array<string,scalar|null>|false
                return new Union([
                    new TArray([
                        Type::getString(),
                        new Union([
                            new TScalar(),
                            new TNull(),
                        ]),
                    ]),
                    new TFalse(),
                ]);

            case 4: // PDO::FETCH_BOTH - array<array-key,scalar|null>|false
                return new Union([
                    new TArray([
                        Type::getArrayKey(),
                        new Union([
                            new TScalar(),
                            new TNull(),
                        ]),
                    ]),
                    new TFalse(),
                ]);

            case 6: // PDO::FETCH_BOUND - bool
                return Type::getBool();

            case 7: // PDO::FETCH_COLUMN - scalar|null|false
                return new Union([
                    new TScalar(),
                    new TNull(),
                    new TFalse(),
                ]);

            case 8: // PDO::FETCH_CLASS - object|false
                return new Union([
                    new TObject(),
                    new TFalse(),
                ]);

            case 1: // PDO::FETCH_LAZY - object|false
                // This actually returns a PDORow object, but that class is
                // undocumented, and its attributes are all dynamic anyway
                return new Union([
                    new TObject(),
                    new TFalse(),
                ]);

            case 11: // PDO::FETCH_NAMED - array<string, scalar|null|list<scalar|null>>|false
                return new Union([
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
                ]);

            case 12: // PDO::FETCH_KEY_PAIR - array<array-key,scalar|null>
                return new Union([
                    new TArray([
                        Type::getArrayKey(),
                        new Union([
                            new TScalar(),
                            new TNull(),
                        ]),
                    ]),
                ]);

            case 3: // PDO::FETCH_NUM - list<scalar|null>|false
                return new Union([
                    Type::getListAtomic(
                        new Union([
                            new TScalar(),
                            new TNull(),
                        ]),
                    ),
                    new TFalse(),
                ]);

            case 5: // PDO::FETCH_OBJ - stdClass|false
                return new Union([
                    new TNamedObject('stdClass'),
                    new TFalse(),
                ]);
        }

        return null;
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

        switch ($fetch_mode) {
            case 2: // PDO::FETCH_ASSOC - list<array<string,scalar|null>>
                return new Union([
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
                ]);

            case 4: // PDO::FETCH_BOTH - list<array<array-key,scalar|null>>
                return new Union([
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
                ]);

            case 6: // PDO::FETCH_BOUND - list<bool>
                return new Union([
                    Type::getListAtomic(
                        Type::getBool(),
                    ),
                ]);

            case 7: // PDO::FETCH_COLUMN - list<scalar|null>
                return new Union([
                    Type::getListAtomic(
                        new Union([
                            new TScalar(),
                            new TNull(),
                        ]),
                    ),
                ]);

            case 8: // PDO::FETCH_CLASS - list<object>
                return new Union([
                    Type::getListAtomic(
                        new Union([
                            $fetch_class_name ? new TNamedObject($fetch_class_name) : new TObject(),
                        ]),
                    ),
                ]);

            case 11: // PDO::FETCH_NAMED - list<array<string, scalar|null|list<scalar|null>>>
                return new Union([
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
                ]);

            case 12: // PDO::FETCH_KEY_PAIR - array<array-key,scalar|null>
                return new Union([
                    new TArray([
                        Type::getArrayKey(),
                        new Union([
                            new TScalar(),
                            new TNull(),
                        ]),
                    ]),
                ]);

            case 3: // PDO::FETCH_NUM - list<list<scalar|null>>
                return new Union([
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
                ]);

            case 5: // PDO::FETCH_OBJ - list<stdClass>
                return new Union([
                    Type::getListAtomic(
                        new Union([
                            new TNamedObject('stdClass'),
                        ]),
                    ),
                ]);
        }

        return null;
    }
}
