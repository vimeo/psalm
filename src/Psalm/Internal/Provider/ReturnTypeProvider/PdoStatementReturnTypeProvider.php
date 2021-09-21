<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;

class PdoStatementReturnTypeProvider implements \Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['PDOStatement'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union
    {
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'fetch'
            && \class_exists('PDO')
            && isset($call_args[0])
            && ($first_arg_type = $source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $first_arg_type->isSingleIntLiteral()
        ) {
            $fetch_mode = $first_arg_type->getSingleIntLiteral()->value;

            switch ($fetch_mode) {
                case \PDO::FETCH_ASSOC: // array<string,scalar|null>|false
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            new Type\Union([
                                new Type\Atomic\TScalar(),
                                new Type\Atomic\TNull()
                            ])
                        ]),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_BOTH: // array<array-key,scalar|null>|false
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getArrayKey(),
                            new Type\Union([
                                new Type\Atomic\TScalar(),
                                new Type\Atomic\TNull()
                            ])
                        ]),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_BOUND: // bool
                    return Type::getBool();

                case \PDO::FETCH_CLASS: // object|false
                    return new Type\Union([
                        new Type\Atomic\TObject(),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_LAZY: // object|false
                    // This actually returns a PDORow object, but that class is
                    // undocumented, and its attributes are all dynamic anyway
                    return new Type\Union([
                        new Type\Atomic\TObject(),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_NAMED: // array<string, scalar|list<scalar>>|false
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            new Type\Union([
                                new Type\Atomic\TScalar(),
                                new Type\Atomic\TList(Type::getScalar())
                            ])
                        ]),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_NUM: // list<scalar|null>|false
                    return new Type\Union([
                        new Type\Atomic\TList(
                            new Type\Union([
                                new Type\Atomic\TScalar(),
                                new Type\Atomic\TNull()
                            ])
                        ),
                        new Type\Atomic\TFalse(),
                    ]);

                case \PDO::FETCH_OBJ: // stdClass|false
                    return new Type\Union([
                        new Type\Atomic\TNamedObject('stdClass'),
                        new Type\Atomic\TFalse(),
                    ]);
            }
        }

        return null;
    }
}
