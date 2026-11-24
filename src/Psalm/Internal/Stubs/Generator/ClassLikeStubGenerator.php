<?php declare(strict_types=1);

namespace Psalm\Internal\Stubs\Generator;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\Scalar\VirtualInt;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\Stmt\VirtualClass;
use Psalm\Node\Stmt\VirtualClassConst;
use Psalm\Node\Stmt\VirtualClassMethod;
use Psalm\Node\Stmt\VirtualInterface;
use Psalm\Node\Stmt\VirtualProperty;
use Psalm\Node\Stmt\VirtualTrait;
use Psalm\Node\Stmt\TraitUseAdaptation\VirtualAlias;
use Psalm\Node\Stmt\TraitUseAdaptation\VirtualPrecedence;
use Psalm\Node\Stmt\VirtualTraitUse;
use Psalm\Node\VirtualConst;
use Psalm\Node\VirtualIdentifier;
use Psalm\Node\VirtualPropertyItem;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\TemplateVariance;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use ReflectionProperty;
use UnexpectedValueException;
use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function in_array;
use function is_int;
use function is_string;
use function rtrim;
use function strtolower;

/**
 * @internal
 */
final class ClassLikeStubGenerator
{
    /**
     * @return PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_|PhpParser\Node\Stmt\Enum_
     */
    public static function getClassLikeNode(
        Codebase $codebase,
        ClassLikeStorage $storage,
        string $classlike_name
    ) : PhpParser\Node\Stmt\ClassLike {
        $trait_uses = $storage->used_traits
            ? [new VirtualTraitUse(
                array_map(
                    static fn(string $trait): VirtualFullyQualified => new VirtualFullyQualified($trait),
                    array_values($storage->used_traits),
                ),
                self::getTraitAdaptations($codebase, $storage),
            )]
            : [];

        $subnodes = [
            'stmts' => [
                ...$trait_uses,
                ...self::getConstantNodes($codebase, $storage),
                ...self::getPropertyNodes($storage),
                ...self::getMethodNodes($storage),
            ]
        ];

        // one line for each template in the declared order, which is the order of the type arguments
        $template_lines = [];

        $template_offset = 0;

        foreach ($storage->template_types ?: [] as $template_name => $map) {
            $type = array_values($map)[0];

            $key = match ($storage->getTemplateVariance($template_offset)) {
                TemplateVariance::Invariant => 'template',
                TemplateVariance::Covariant => 'template-covariant',
                TemplateVariance::Contravariant => 'template-contravariant',
            };

            $template_lines[] = ' * @' . $key . ' ' . $template_name . ' as ' . $type->toNamespacedString(
                null,
                [],
                null,
                false
            );

            $template_offset++;
        }

        $attrs = [
            'comments' => $template_lines
                ? [new PhpParser\Comment\Doc("/**\n" . implode("\n", $template_lines) . "\n */")]
                : []
        ];

        if ($storage->is_interface) {
            if ($storage->direct_interface_parents) {
                $subnodes['extends'] = [];

                foreach ($storage->direct_interface_parents as $direct_interface_parent) {
                    $subnodes['extends'][] = new VirtualFullyQualified($direct_interface_parent);
                }
            }

            return new VirtualInterface(
                $classlike_name,
                $subnodes,
                $attrs
            );
        }

        if ($storage->is_trait) {
            return new VirtualTrait(
                $classlike_name,
                $subnodes,
                $attrs
            );
        }

        $subnodes['implements'] = [];

        foreach ($storage->direct_class_interfaces as $direct_class_interface_lc => $direct_class_interface) {
            // every enum implements these
            if ($storage->is_enum
                && ($direct_class_interface_lc === 'unitenum' || $direct_class_interface_lc === 'backedenum')
            ) {
                continue;
            }

            $subnodes['implements'][] = new VirtualFullyQualified($direct_class_interface);
        }

        if ($storage->is_enum) {
            $case_values = [];

            foreach ($storage->enum_cases as $case_name => $case_storage) {
                $case_values[$case_name] = $case_storage->getValue($codebase->classlikes)?->value;
            }

            $case_nodes = [];

            foreach ($case_values as $case_name => $case_value) {
                // a backed case needs a value, and a unique one: give the cases Psalm can't resolve a placeholder
                if ($case_value === null && $storage->enum_type === 'int') {
                    $case_value = 0;

                    while (in_array($case_value, $case_values, true)) {
                        $case_value++;
                    }

                    $case_values[$case_name] = $case_value;
                } elseif ($case_value === null && $storage->enum_type === 'string') {
                    $case_value = StubsGenerator::UNKNOWN_VALUE . ': ' . $case_name;

                    while (in_array($case_value, $case_values, true)) {
                        $case_value .= '_';
                    }

                    $case_values[$case_name] = $case_value;
                }

                $case_nodes[] = new PhpParser\Node\Stmt\EnumCase(
                    $case_name,
                    is_int($case_value)
                        ? new VirtualInt($case_value)
                        : (is_string($case_value) ? new VirtualString($case_value) : null),
                );
            }

            $subnodes['stmts'] = [...$case_nodes, ...$subnodes['stmts']];
            $subnodes['scalarType'] = $storage->enum_type !== null
                ? new VirtualIdentifier($storage->enum_type)
                : null;

            return new PhpParser\Node\Stmt\Enum_(
                $classlike_name,
                $subnodes,
                $attrs,
            );
        }

        if ($storage->parent_class) {
            $subnodes['extends'] = new VirtualFullyQualified($storage->parent_class);
        }

        $subnodes['flags'] = ($storage->abstract ? PhpParser\Modifiers::ABSTRACT : 0)
            | ($storage->final && !$storage->final_from_docblock ? PhpParser\Modifiers::FINAL : 0)
            | ($storage->readonly ? PhpParser\Modifiers::READONLY : 0);

        return new VirtualClass(
            $classlike_name,
            $subnodes,
            $attrs
        );
    }

    /**
     * The `insteadof` and `as` rules of the trait use, rebuilt from what the storage keeps of them
     *
     * @return list<PhpParser\Node\Stmt\TraitUseAdaptation>
     */
    private static function getTraitAdaptations(Codebase $codebase, ClassLikeStorage $storage): array
    {
        $adaptations = [];

        foreach ($storage->trait_excluded_methods as $method_name => $excluded_traits) {
            foreach ($storage->used_traits as $trait_lc => $trait) {
                if (isset($excluded_traits[$trait_lc])
                    || !$codebase->classlike_storage_provider->has($trait)
                    || !isset($codebase->classlike_storage_provider->get($trait)->declaring_method_ids[$method_name])
                ) {
                    continue;
                }

                $adaptations[] = new VirtualPrecedence(
                    new VirtualFullyQualified($trait),
                    $method_name,
                    array_map(
                        static fn(string $excluded): VirtualFullyQualified => new VirtualFullyQualified(
                            $storage->used_traits[$excluded] ?? $excluded,
                        ),
                        array_keys($excluded_traits),
                    ),
                );

                break;
            }
        }

        $aliased = [];

        foreach ($storage->trait_alias_map_cased as $alias => $method_name) {
            $alias_lc = strtolower($alias);
            $aliased[$alias_lc] = true;
            $trait_lc = $storage->trait_alias_trait_map[$alias_lc] ?? null;

            $adaptations[] = new VirtualAlias(
                $trait_lc !== null ? new VirtualFullyQualified($storage->used_traits[$trait_lc] ?? $trait_lc) : null,
                $method_name,
                self::getTraitModifier($storage, $alias_lc),
                $alias,
            );
        }

        // `use T { f as protected; }` changes the visibility of the method without an alias
        foreach ($storage->trait_visibility_map + $storage->trait_final_map as $method_name => $_) {
            if (!isset($aliased[$method_name])) {
                $adaptations[] = new VirtualAlias(
                    null,
                    $method_name,
                    self::getTraitModifier($storage, $method_name),
                    null,
                );
            }
        }

        return $adaptations;
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getTraitModifier(ClassLikeStorage $storage, string $name_lc): ?int
    {
        if (isset($storage->trait_final_map[$name_lc])) {
            return PhpParser\Modifiers::FINAL;
        }

        return match ($storage->trait_visibility_map[$name_lc] ?? null) {
            ClassLikeAnalyzer::VISIBILITY_PUBLIC => PhpParser\Modifiers::PUBLIC,
            ClassLikeAnalyzer::VISIBILITY_PROTECTED => PhpParser\Modifiers::PROTECTED,
            ClassLikeAnalyzer::VISIBILITY_PRIVATE => PhpParser\Modifiers::PRIVATE,
            default => null,
        };
    }

    /**
     * @return list<PhpParser\Node\Stmt\ClassConst>
     */
    private static function getConstantNodes(Codebase $codebase, ClassLikeStorage $storage): array
    {
        $constant_nodes = [];

        foreach ($storage->constants as $constant_name => $constant_storage) {
            if (self::isInheritedConstant($codebase, $storage, $constant_name, $constant_storage)) {
                continue;
            }

            if ($constant_storage->unresolved_node) {
                $type = new Union([
                    ConstantTypeResolver::resolve(
                        $codebase->classlikes,
                        $constant_storage->unresolved_node
                    )
                ]);
            } elseif ($constant_storage->type) {
                $type = $constant_storage->type;
            } else {
                throw new UnexpectedValueException('bad');
            }

            $value = StubsGenerator::getExpressionFromType($type);

            // the placeholder of a value Psalm can't infer would not match the type
            $signature_type = $value instanceof PhpParser\Node\Scalar\String_
                && $value->value === StubsGenerator::UNKNOWN_VALUE
                ? null
                : $constant_storage->signature_type;

            $constant_nodes[] = new VirtualClassConst(
                [
                    new VirtualConst(
                        $constant_name,
                        $value
                    )
                ],
                ($constant_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    ? PhpParser\Modifiers::PUBLIC
                    : ($constant_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                        ? PhpParser\Modifiers::PROTECTED
                        : PhpParser\Modifiers::PRIVATE))
                    | ($constant_storage->final ? PhpParser\Modifiers::FINAL : 0),
                [],
                [],
                $signature_type
                    ? StubsGenerator::getParserTypeFromPsalmType($signature_type)
                    : null,
            );
        }

        return $constant_nodes;
    }

    /**
     * Constants of parent classes and interfaces are declared there
     *
     * @psalm-capabilities read-props
     */
    private static function isInheritedConstant(
        Codebase $codebase,
        ClassLikeStorage $storage,
        string $constant_name,
        ClassConstantStorage $constant_storage,
    ): bool {
        $parents = [...$storage->parent_classes, ...$storage->parent_interfaces, ...$storage->class_implements];

        foreach ($parents as $parent) {
            if (!$codebase->classlike_storage_provider->has($parent)) {
                continue;
            }

            $parent_constant = $codebase->classlike_storage_provider->get($parent)->constants[$constant_name] ?? null;

            if (!$parent_constant) {
                continue;
            }

            // the constants of a built-in class read by reflection have no location
            if ($parent_constant->location?->getHash() === $constant_storage->location?->getHash()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<PhpParser\Node\Stmt\Property>
     */
    private static function getPropertyNodes(ClassLikeStorage $storage): array
    {
        $namespace_name = implode('\\', array_slice(explode('\\', $storage->name), 0, -1));

        $property_nodes = [];

        foreach ($storage->properties as $property_name => $property_storage) {
            // declared by PHP for every enum
            if ($storage->is_enum && ($property_name === 'name' || $property_name === 'value')) {
                continue;
            }

            $flag = match ($property_storage->visibility) {
                ClassLikeAnalyzer::VISIBILITY_PRIVATE => PhpParser\Modifiers::PRIVATE,
                ClassLikeAnalyzer::VISIBILITY_PROTECTED => PhpParser\Modifiers::PROTECTED,
                default => PhpParser\Modifiers::PUBLIC,
            };

            $docblock = new ParsedDocblock('', []);

            if ($property_storage->type
                && $property_storage->signature_type !== $property_storage->type
            ) {
                $docblock->tags['var'][] = $property_storage->type->toNamespacedString(
                    $namespace_name,
                    [],
                    null,
                    false
                );
            }

            $property_nodes[] = new VirtualProperty(
                $flag
                    | ($property_storage->is_static ? PhpParser\Modifiers::STATIC : 0)
                    // a readonly class makes its properties readonly
                    | ($property_storage->native_readonly && !$storage->readonly ? PhpParser\Modifiers::READONLY : 0),
                [
                    new VirtualPropertyItem(
                        $property_name,
                        $property_storage->suggested_type
                            ? StubsGenerator::getExpressionFromType($property_storage->suggested_type)
                            : null
                    )
                ],
                [
                    'comments' => $docblock->tags
                        ? [
                            new PhpParser\Comment\Doc(
                                rtrim($docblock->render('        '))
                            )
                        ]
                        : []
                ],
                $property_storage->signature_type
                    ? StubsGenerator::getParserTypeFromPsalmType($property_storage->signature_type)
                    : null
            );
        }

        return $property_nodes;
    }

    /**
     * @return list<PhpParser\Node\Stmt\ClassMethod>
     */
    private static function getMethodNodes(ClassLikeStorage $storage): array {
        $namespace_name = implode('\\', array_slice(explode('\\', $storage->name), 0, -1));
        $method_nodes = [];

        foreach ($storage->methods as $method_storage) {
            if (!$method_storage->cased_name) {
                throw new UnexpectedValueException('very bad');
            }

            $flag = match ($method_storage->visibility) {
                ReflectionProperty::IS_PRIVATE => PhpParser\Modifiers::PRIVATE,
                ReflectionProperty::IS_PROTECTED => PhpParser\Modifiers::PROTECTED,
                default => PhpParser\Modifiers::PUBLIC,
            };

            $method_nodes[] = new VirtualClassMethod(
                $method_storage->cased_name,
                [
                    'flags' => $flag
                        | ($method_storage->is_static ? PhpParser\Modifiers::STATIC : 0)
                        | ($method_storage->abstract ? PhpParser\Modifiers::ABSTRACT : 0)
                        // the methods of a class written as final are final too
                        | ($method_storage->final
                            && !($storage->final && !$storage->final_from_docblock)
                            && !$method_storage->final_from_docblock
                            ? PhpParser\Modifiers::FINAL
                            : 0),
                    'params' => StubsGenerator::getFunctionParamNodes($method_storage),
                    'returnType' => $method_storage->signature_return_type
                        ? StubsGenerator::getParserTypeFromPsalmType($method_storage->signature_return_type)
                        : null,
                    'stmts' =>  $storage->is_interface || $method_storage->abstract ? null : [],
                ],
                ['comments' => StubsGenerator::getDocblockComments($method_storage, $namespace_name)]
            );
        }

        return $method_nodes;
    }
}
