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
class PdoStatementReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return ['PDOStatement'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $config = Config::getInstance();
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'fetch'
            && $config->php_extensions["pdo"]
            && isset($call_args[0])
            && ($first_arg_type = $source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $first_arg_type->isSingleIntLiteral()
        ) {
            $fetch_mode = $first_arg_type->getSingleIntLiteral()->value;

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

                case 11: // PDO::FETCH_NAMED - array<string, scalar|list<scalar>>|false
                    return new Union([
                        new TArray([
                            Type::getString(),
                            new Union([
                                new TScalar(),
                                Type::getListAtomic(Type::getScalar()),
                            ]),
                        ]),
                        new TFalse(),
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
        }

        return null;
    }
}
