<?php

namespace Psalm\Internal\Stubs\Generator;

use Psalm\Codebase;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\FunctionStorage;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TAssertionFalsy;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\Scalar;
use PhpParser;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Expr\VirtualClassConstFetch;
use Psalm\Node\Expr\VirtualConstFetch;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\Scalar\VirtualDNumber;
use Psalm\Node\Scalar\VirtualLNumber;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\Stmt\VirtualFunction;
use Psalm\Node\Stmt\VirtualNamespace;
use Psalm\Node\VirtualConst;
use Psalm\Node\Stmt\VirtualConst as StmtVirtualConst_;
use Psalm\Node\VirtualIdentifier;
use Psalm\Node\VirtualName;
use Psalm\Node\VirtualNullableType;
use Psalm\Node\VirtualParam;
use Psalm\Type;
use Psalm\Type\Union;

use UnexpectedValueException;
use function dirname;
use function is_int;
use function rtrim;
use function strpos;

/**
 * @internal
 *
 * @psalm-type NamespacedNodes=array<string, array<string, PhpParser\Node\Stmt>>
 */
class StubsGenerator
{
    public static function getAll(
        Codebase $codebase,
        ClassLikeStorageProvider $class_provider,
        FileStorageProvider $file_provider
    ): string {
        $namespaced_nodes = [];

        $psalm_base = dirname(__DIR__, 5);

        $classes = [];
        foreach ($class_provider->getAll() as $storage) {
            if (strpos($storage->name, 'Psalm\\') === 0) {
                continue;
            }

            if ($storage->location
                && strpos($storage->location->file_path, $psalm_base) === 0
            ) {
                continue;
            }

            if ($storage->stubbed) {
                continue;
            }

            $classes[] = $storage;
        }
        self::addClassLikeStubs($namespaced_nodes, $classes, $codebase);

        $stubbed_functions = [];
        foreach ($codebase->functions->getAllStubbedFunctions() as $function_storage) {
            if ($function_storage->location
                && strpos($function_storage->location->file_path, $psalm_base) === 0
            ) {
                continue;
            }

            $stubbed_functions[] = $function_storage;
        }
        self::addFunctionStubs($namespaced_nodes, $stubbed_functions);
        self::addConstantStubs($namespaced_nodes, $codebase->getAllStubbedConstants());

        $file_functions = $file_constants = [];
        foreach ($file_provider->getAll() as $file_storage) {
            if (strpos($file_storage->file_path, $psalm_base) === 0) {
                continue;
            }

            foreach ($file_storage->functions as $function_storage) {
                if (!$function_storage->cased_name) {
                    continue;
                }

                $file_functions[] = $function_storage;
            }

            foreach ($file_storage->constants as $fq_name => $type) {
                $file_constants[$fq_name] = $type;
            }
        }
        self::addFunctionStubs($namespaced_nodes, $file_functions);
        self::addConstantStubs($namespaced_nodes, $file_constants);

        ksort($namespaced_nodes);

        return self::printNamespacedNodes($namespaced_nodes);
    }

    /**
     * @param NamespacedNodes $namespaced_nodes
     */
    public static function printNamespacedNodes(array $namespaced_nodes, ?string $file_comment = null): string
    {
        $namespace_stmts = [];

        foreach ($namespaced_nodes as $namespace_name => $stmts) {
            ksort($stmts);

            $namespace_stmts[] = new VirtualNamespace(
                $namespace_name ? new VirtualName($namespace_name) : null,
                array_values($stmts),
                ['kind' => PhpParser\Node\Stmt\Namespace_::KIND_BRACED]
            );
        }

        if ($file_comment !== null && !empty($namespace_stmts)) {
            $namespace_stmts[0]->setAttribute("comments", [new PhpParser\Comment($file_comment)]);
        }

        $prettyPrinter = new PhpParser\PrettyPrinter\Standard(["shortArraySyntax" => true]);
        return $prettyPrinter->prettyPrintFile($namespace_stmts);
    }

    private static function getFunctionNode(
        FunctionLikeStorage $function_storage,
        string $function_name,
        string $namespace_name
    ) : PhpParser\Node\Stmt\Function_ {
        $docblock = new ParsedDocblock('', []);

        foreach ($function_storage->template_types ?: [] as $template_name => $map) {
            $type = array_values($map)[0];

            $docblock->tags['template'][] = $template_name . ' as ' . $type->toNamespacedString(
                $namespace_name,
                [],
                null,
                false
            );
        }

        foreach ($function_storage->params as $param) {
            if ($param->type && $param->type !== $param->signature_type) {
                $docblock->tags['param'][] = $param->type->toNamespacedString(
                    $namespace_name,
                    [],
                    null,
                    false
                ) . ' $' . $param->name;
            }
        }

        if ($function_storage->return_type
            && $function_storage->signature_return_type !== $function_storage->return_type
        ) {
            $docblock->tags['return'][] = $function_storage->return_type->toNamespacedString(
                $namespace_name,
                [],
                null,
                false
            );
        }

        foreach ($function_storage->throws ?: [] as $exception_name => $_) {
            $docblock->tags['throws'][] = Type::getStringFromFQCLN(
                $exception_name,
                $namespace_name,
                [],
                null,
                false
            );
        }

        return new VirtualFunction(
            $function_name,
            [
                'params' => self::getFunctionParamNodes($function_storage),
                'returnType' => $function_storage->signature_return_type
                    ? self::getParserTypeFromPsalmType($function_storage->signature_return_type)
                    : null,
                'stmts' => [],
            ],
            [
                'comments' => $docblock->tags
                    ? [
                        new PhpParser\Comment\Doc(
                            rtrim($docblock->render('        '))
                        )
                    ]
                    : []
            ]
        );
    }

    /**
     * @return list<PhpParser\Node\Param>
     */
    public static function getFunctionParamNodes(FunctionLikeStorage $method_storage): array
    {
        $param_nodes = [];

        foreach ($method_storage->params as $param) {
            $param_nodes[] = new VirtualParam(
                new VirtualVariable($param->name),
                $param->default_type instanceof Union
                    ? self::getExpressionFromType($param->default_type)
                    : null,
                $param->signature_type
                    ? self::getParserTypeFromPsalmType($param->signature_type)
                    : null,
                $param->by_ref,
                $param->is_variadic
            );
        }

        return $param_nodes;
    }

    /**
     * @return PhpParser\Node\Identifier|PhpParser\Node\Name\FullyQualified|PhpParser\Node\NullableType|null
     */
    public static function getParserTypeFromPsalmType(Union $type): ?PhpParser\NodeAbstract
    {
        $nullable = $type->isNullable();

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNull) {
                continue;
            }

            if ($atomic_type instanceof Scalar
                || $atomic_type instanceof TObject
                || $atomic_type instanceof TArray
                || $atomic_type instanceof TIterable
            ) {
                $identifier_string = $atomic_type->toPhpString(null, [], null, 8_00_00);

                if ($identifier_string === null) {
                    throw new UnexpectedValueException(
                        $atomic_type->getId() . ' could not be converted to an identifier'
                    );
                }
                $identifier = new VirtualIdentifier($identifier_string);

                if ($nullable) {
                    return new VirtualNullableType($identifier);
                }

                return $identifier;
            }

            if ($atomic_type instanceof TNamedObject) {
                $name_node = new VirtualFullyQualified($atomic_type->value);

                if ($nullable) {
                    return new VirtualNullableType($name_node);
                }

                return $name_node;
            }
        }

        return null;
    }

    public static function getExpressionFromType(Union $type) : PhpParser\Node\Expr
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TLiteralClassString) {
                return new VirtualClassConstFetch(new VirtualName('\\' . $atomic_type->value), new VirtualIdentifier('class'));
            }

            if ($atomic_type instanceof TLiteralString) {
                return new VirtualString($atomic_type->value);
            }

            if ($atomic_type instanceof TLiteralInt) {
                return new VirtualLNumber($atomic_type->value);
            }

            if ($atomic_type instanceof TLiteralFloat) {
                return new VirtualDNumber($atomic_type->value);
            }

            if ($atomic_type instanceof TFalse) {
                return new VirtualConstFetch(new VirtualName('false'));
            }

            if ($atomic_type instanceof TTrue) {
                return new VirtualConstFetch(new VirtualName('true'));
            }

            if ($atomic_type instanceof TNull) {
                return new VirtualConstFetch(new VirtualName('null'));
            }

            if ($atomic_type instanceof TArray) {
                return new VirtualArray([]);
            }

            if ($atomic_type instanceof TKeyedArray) {
                $new_items = [];

                foreach ($atomic_type->properties as $property_name => $property_type) {
                    if ($atomic_type->is_list) {
                        $key_type = null;
                    } elseif (is_int($property_name)) {
                        $key_type = new VirtualLNumber($property_name);
                    } else {
                        $key_type = new VirtualString($property_name);
                    }

                    $new_items[] = new VirtualArrayItem(
                        self::getExpressionFromType($property_type),
                        $key_type
                    );
                }

                return new VirtualArray($new_items);
            }

            if ($atomic_type instanceof TEnumCase) {
                return new VirtualClassConstFetch(new VirtualName('\\' . $atomic_type->value), new VirtualIdentifier($atomic_type->case_name));
            }
        }

        return new VirtualString('Psalm could not infer this type');
    }

    /**
     * @param NamespacedNodes $namespaced_nodes
     * @param iterable<mixed, ClassLikeStorage> $classlike_storages
     */
    public static function addClassLikeStubs(
        array &$namespaced_nodes,
        iterable $classlike_storages,
        Codebase $codebase
    ): void {
        foreach ($classlike_storages as $classlike_storage) {
            $name_parts = explode('\\', $classlike_storage->name);

            $classlike_name = array_pop($name_parts);
            $namespace_name = implode('\\', $name_parts);

            if (!isset($namespaced_nodes[$namespace_name])) {
                $namespaced_nodes[$namespace_name] = [];
            }

            $namespaced_nodes[$namespace_name][$classlike_name] = ClassLikeStubGenerator::getClassLikeNode(
                $codebase,
                $classlike_storage,
                $classlike_name
            );
        }
    }

    /**
     * @param NamespacedNodes $namespaced_nodes
     * @param iterable<mixed, FunctionStorage> $function_storages
     */
    public static function addFunctionStubs(array &$namespaced_nodes, iterable $function_storages): void
    {
        foreach ($function_storages as $function_storage) {
            if (!$function_storage->cased_name) {
                throw new UnexpectedValueException('Missing cased name for function');
            }

            $fq_name = $function_storage->cased_name;

            $name_parts = explode('\\', $fq_name);
            $function_name = array_pop($name_parts);

            $namespace_name = implode('\\', $name_parts);

            $namespaced_nodes[$namespace_name][$function_name] = self::getFunctionNode(
                $function_storage,
                $function_name,
                $namespace_name
            );
        }
    }

    /**
     * @param NamespacedNodes $namespaced_nodes
     * @param iterable<string, Union> $constants
     */
    public static function addConstantStubs(array &$namespaced_nodes, iterable $constants): void
    {
        foreach ($constants as $const_fq_name => $const_type) {
            if ($const_type->isMixed()) {
                // TODO allow overriding const type with @var tag and allow mixed value constants
                continue;
            }

            $name_parts = explode('\\', $const_fq_name);
            $const_name = array_pop($name_parts);

            $namespace_name = implode('\\', $name_parts);

            $namespaced_nodes[$namespace_name][$const_name] = new StmtVirtualConst_(
                [
                    new VirtualConst(
                        $const_name,
                        self::getExpressionFromType($const_type)
                    )
                ]
            );
        }
    }
}
