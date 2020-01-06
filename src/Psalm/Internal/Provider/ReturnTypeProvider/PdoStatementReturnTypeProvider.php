<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class PdoStatementReturnTypeProvider implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['PDOStatement'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     * @return ?Type\Union
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        array $template_type_parameters = null,
        string $called_fq_classlike_name = null,
        string $called_method_name_lowercase = null
    ) {
        if ($method_name_lowercase === 'fetch'
            && \class_exists('PDO')
            && isset($call_args[0])
            && ($first_arg_type = $source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $first_arg_type->isSingleIntLiteral()
        ) {
            $fetch_mode = $first_arg_type->getSingleIntLiteral()->value;

            switch ($fetch_mode) {
                case \PDO::FETCH_ASSOC: // array<string,scalar>
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            Type::getScalar()
                        ]),
                    ]);

                case \PDO::FETCH_BOTH: // array<array-key,scalar>
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getArrayKey(),
                            Type::getScalar()
                        ]),
                    ]);

                case \PDO::FETCH_BOUND: // true
                    return Type::getTrue();

                case \PDO::FETCH_CLASS: // object
                    return Type::getObject();

                case \PDO::FETCH_LAZY: // object
                    // This actually returns a PDORow object, but that class is
                    // undocumented, and its attributes are all dynamic anyway
                    return Type::getObject();

                case \PDO::FETCH_NAMED: // array<string, scalar|list<scalar>>
                    return new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getString(),
                            new Type\Union([
                                new Type\Atomic\TScalar(),
                                new Type\Atomic\TList(Type::getScalar())
                            ])
                        ]),
                    ]);

                case \PDO::FETCH_NUM: // list<scalar>
                    return new Type\Union([
                        new Type\Atomic\TList(Type::getScalar())
                    ]);

                case \PDO::FETCH_OBJ: // stdClass
                    return new Type\Union([
                        new Type\Atomic\TNamedObject('stdClass')
                    ]);
            }
        }
    }
}
