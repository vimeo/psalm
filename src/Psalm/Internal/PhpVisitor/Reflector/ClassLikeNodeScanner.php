<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use Exception;
use InvalidArgumentException;
use LogicException;
use PhpParser;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\InvalidClasslikeOverrideException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Codebase\PropertyMap;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\PropertyIdentifier;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\ClassLikeDocblockComment;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeAlias\ClassTypeAlias;
use Psalm\Internal\Type\TypeAlias\InlineTypeAlias;
use Psalm\Internal\Type\TypeAlias\LinkableTypeAlias;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Interner;
use Psalm\Issue\ConstantDeclarationInTrait;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\DuplicateConstant;
use Psalm\Issue\DuplicateEnumCase;
use Psalm\Issue\DuplicateProperty;
use Psalm\Issue\InvalidAttribute;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidEnumBackingType;
use Psalm\Issue\InvalidEnumCaseValue;
use Psalm\Issue\InvalidTypeImport;
use Psalm\Issue\MissingClassConstType;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\ParseError;
use Psalm\IssueBuffer;
use Psalm\Storage\AttributeStorage;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\EnumCaseStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Mutations;
use Psalm\Storage\PropertyHookStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_pop;
use function array_shift;
use function array_values;
use function count;
use function implode;
use function ltrim;
use function min;
use function preg_match;
use function preg_split;
use function sprintf;
use function strtolower;
use function trim;
use function usort;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * @internal
 */
final class ClassLikeNodeScanner
{
    private readonly string $file_path;

    private readonly Config $config;

    /**
     * @var array<int, InlineTypeAlias> alias name id => alias
     */
    private array $classlike_type_aliases = [];

    /**
     * @var array<int, array<int, Union>> template name id => defining entity id => type
     */
    public array $class_template_types = [];

    public ?ClassLikeStorage $storage = null;

    /**
     * @var array<int, TypeAlias> alias name id => alias
     */
    public array $type_aliases = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
        private readonly FileStorage $file_storage,
        private readonly FileScanner $file_scanner,
        private readonly Aliases $aliases,
        private readonly ?Name $namespace_name,
    ) {
        $this->file_path = $file_storage->file_path;
        $this->config = Config::getInstance();
    }

    /**
     * @return false|null
     * @psalm-suppress ComplexMethod
     */
    public function start(PhpParser\Node\Stmt\ClassLike $node): ?bool
    {
        $class_location = new CodeLocation($this->file_scanner, $node);
        $name_location = null;

        $storage = null;

        $class_name = null;

        $is_classlike_overridden = false;

        if ($node->name === null) {
            if (!$node instanceof PhpParser\Node\Stmt\Class_) {
                throw new LogicException('Anonymous classes are always classes');
            }

            $fq_classlike_name = ClassAnalyzer::getAnonymousClassName($node, $this->aliases, $this->file_path);
        } else {
            $name_location = new CodeLocation($this->file_scanner, $node->name);

            $fq_classlike_name = Interner::intern(
                ($this->aliases->namespace !== null ? Interner::str($this->aliases->namespace) . '\\' : '')
                . $node->name->name,
            );

            $class_name = $node->name->name;

            if ($this->codebase->classlike_storage_provider->has($fq_classlike_name)) {
                $duplicate_storage = $this->codebase->classlike_storage_provider->get($fq_classlike_name);

                // don't override data from files that are getting analyzed with data from stubs
                // if the stubs contain the same class
                if (!$duplicate_storage->stubbed
                    && $this->codebase->register_stub_files
                    && $duplicate_storage->stmt_location
                    && $this->config->isInProjectDirs($duplicate_storage->stmt_location->file_path)) {
                    return false;
                }

                if (!$this->codebase->register_stub_files) {
                    if (!$duplicate_storage->stmt_location
                        || $duplicate_storage->stmt_location->file_path !== $this->file_path
                        || $class_location->getHash() !== $duplicate_storage->stmt_location->getHash()
                    ) {
                        IssueBuffer::maybeAdd(
                            new DuplicateClass(
                                'Class ' . Interner::str($fq_classlike_name) . ' has already been defined'
                                . ($duplicate_storage->location
                                    ? ' in ' . $duplicate_storage->location->file_path
                                    : ''),
                                $name_location,
                            ),
                        );

                        $this->file_storage->has_visitor_issues = true;

                        $duplicate_storage->has_visitor_issues = true;

                        return false;
                    }
                } elseif (!$duplicate_storage->location
                    || $duplicate_storage->location->file_path !== $this->file_path
                    || $class_location->getHash() !== $duplicate_storage->location->getHash()
                ) {
                    $is_classlike_overridden = true;
                    // we're overwriting some methods
                    $storage = $this->storage = $duplicate_storage;
                    $this->codebase->classlike_storage_provider->makeNew($fq_classlike_name);
                    $storage->populated = false;
                    $storage->class_implements = []; // we do this because reflection reports
                    $storage->parent_interfaces = [];
                    $storage->stubbed = true;
                    $storage->aliases = $this->aliases;

                    foreach ($storage->dependent_classlikes as $dependent_name => $_) {
                        try {
                            $dependent_storage = $this->codebase->classlike_storage_provider->get($dependent_name);
                        } catch (InvalidArgumentException) {
                            continue;
                        }
                        $dependent_storage->populated = false;
                        $this->codebase->classlike_storage_provider->makeNew($dependent_name);
                    }
                }
            }
        }

        $this->file_storage->classlikes_in_file[$fq_classlike_name] = $fq_classlike_name;

        if (!$storage) {
            $this->storage = $storage = $this->codebase->classlike_storage_provider->create($fq_classlike_name);
        }

        if ($class_name
            && isset($this->aliases->uses[Interner::intern($class_name)])
            && $this->aliases->uses[Interner::intern($class_name)] !== $fq_classlike_name
        ) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Class name ' . $class_name . ' clashes with a use statement alias',
                    $name_location ?? $class_location,
                ),
            );

            $storage->has_visitor_issues = true;
            $this->file_storage->has_visitor_issues = true;
        }

        $storage->stmt_location = $class_location;
        $storage->location = $name_location;
        if ($this->namespace_name) {
            $storage->namespace_name_location = new CodeLocation($this->file_scanner, $this->namespace_name);
        }
        $storage->user_defined = !$this->codebase->register_stub_files;
        $storage->stubbed = $this->codebase->register_stub_files;
        $storage->aliases = $this->aliases;

        if ($node instanceof PhpParser\Node\Stmt\Class_) {
            $storage->abstract = $node->isAbstract();
            $storage->final = $node->isFinal();
            $storage->readonly = $node->isReadonly();

            $this->codebase->classlikes->addFullyQualifiedClassName($fq_classlike_name, $this->file_path);

            if ($node->extends) {
                $parent_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($node->extends, $this->aliases);
                $parent_fqcln = $this->codebase->classlikes->getUnAliasedName($parent_fqcln);
                $this->codebase->scanner->queueClassLikeForScanning(
                    $parent_fqcln,
                    $this->file_scanner->will_analyze,
                );
                $storage->parent_class = $parent_fqcln;
                $storage->parent_classes[$parent_fqcln] = $parent_fqcln;
                $this->file_storage->required_classes[$parent_fqcln] = $parent_fqcln;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Interface_) {
            $storage->is_interface = true;
            $this->codebase->classlikes->addFullyQualifiedInterfaceName($fq_classlike_name, $this->file_path);

            foreach ($node->extends as $interface) {
                $interface_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($interface, $this->aliases);
                $interface_fqcln = $this->codebase->classlikes->getUnAliasedName($interface_fqcln);
                $this->codebase->scanner->queueClassLikeForScanning($interface_fqcln);
                $storage->parent_interfaces[$interface_fqcln] = $interface_fqcln;
                $storage->direct_interface_parents[$interface_fqcln] = $interface_fqcln;
                $this->file_storage->required_interfaces[$interface_fqcln] = $interface_fqcln;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Trait_) {
            $storage->is_trait = true;
            $this->codebase->classlikes->addFullyQualifiedTraitName($fq_classlike_name, $this->file_path);
        } elseif ($node instanceof PhpParser\Node\Stmt\Enum_) {
            $storage->is_enum = true;
            $storage->final = true;

            if ($node->scalarType) {
                if ($node->scalarType->name === 'string' || $node->scalarType->name === 'int') {
                    $storage->enum_type = $node->scalarType->name;
                    $storage->class_implements[StrId::BackedEnum] = StrId::BackedEnum;
                    $storage->direct_class_interfaces[StrId::BackedEnum] = StrId::BackedEnum;
                    $this->file_storage->required_interfaces[StrId::BackedEnum] = StrId::BackedEnum;
                    $this->codebase->scanner->queueClassLikeForScanning(StrId::BackedEnum);
                    $storage->declaring_method_ids[StrId::from] = new MethodIdentifier(StrId::BackedEnum, StrId::from);
                    $storage->appearing_method_ids[StrId::from] = $storage->declaring_method_ids[StrId::from];
                    $storage->declaring_method_ids[StrId::tryFrom] = new MethodIdentifier(
                        StrId::BackedEnum,
                        StrId::tryFrom,
                    );
                    $storage->appearing_method_ids[StrId::tryFrom] = $storage->declaring_method_ids[StrId::tryFrom];
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidEnumBackingType(
                            'Enums cannot be backed by ' . $node->scalarType->name . ', string or int expected',
                            new CodeLocation($this->file_scanner, $node->scalarType),
                            $fq_classlike_name,
                        ),
                    );
                    $this->file_storage->has_visitor_issues = true;
                    $storage->has_visitor_issues = true;
                }
            }

            $this->codebase->scanner->queueClassLikeForScanning(StrId::UnitEnum);
            $storage->class_implements[StrId::UnitEnum] = StrId::UnitEnum;
            $storage->direct_class_interfaces[StrId::UnitEnum] = StrId::UnitEnum;
            $this->file_storage->required_interfaces[StrId::UnitEnum] = StrId::UnitEnum;
            $storage->final = true;

            $storage->declaring_method_ids[StrId::cases] = new MethodIdentifier(
                StrId::UnitEnum,
                StrId::cases,
            );
            $storage->appearing_method_ids[StrId::cases] = $storage->declaring_method_ids[StrId::cases];

            $this->codebase->classlikes->addFullyQualifiedEnumName($fq_classlike_name, $this->file_path);
        } else {
            throw new UnexpectedValueException('Unknown classlike type');
        }

        if ($node instanceof PhpParser\Node\Stmt\Class_ || $node instanceof PhpParser\Node\Stmt\Enum_) {
            foreach ($node->implements as $interface) {
                $interface_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($interface, $this->aliases);
                $this->codebase->scanner->queueClassLikeForScanning($interface_fqcln);
                $storage->class_implements[$interface_fqcln] = $interface_fqcln;
                $storage->direct_class_interfaces[$interface_fqcln] = $interface_fqcln;
                $this->file_storage->required_interfaces[$interface_fqcln] = $interface_fqcln;
            }
        }

        $docblock_info = null;
        $doc_comment = $node->getDocComment();
        if ($doc_comment) {
            try {
                $docblock_info = ClassLikeDocblockParser::parse(
                    $node,
                    $doc_comment,
                    $this->aliases,
                );

                $this->type_aliases += $this->getImportedTypeAliases($docblock_info, $fq_classlike_name);
            } catch (DocblockParseException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . Interner::str($fq_classlike_name),
                    $name_location ?? $class_location,
                );
            }
        }

        foreach ($node->getComments() as $comment) {
            if (!$comment instanceof PhpParser\Comment\Doc) {
                continue;
            }

            try {
                $type_aliases = self::getTypeAliasesFromComment(
                    $comment,
                    $this->aliases,
                    $this->type_aliases,
                    $fq_classlike_name,
                );

                foreach ($type_aliases as $type_alias) {
                    // finds issues, if there are any
                    TypeParser::parseTokens($type_alias->replacement_tokens);
                }

                $this->type_aliases += $type_aliases;

                if ($type_aliases) {
                    $this->classlike_type_aliases = $type_aliases;
                }
            } catch (DocblockParseException | TypeParseTreeException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );
            }
        }

        if ($docblock_info) {
            if ($docblock_info->stub_override && !$is_classlike_overridden) {
                throw new InvalidClasslikeOverrideException(
                    'Class/interface/trait ' . Interner::str($fq_classlike_name) . ' is marked as stub override,'
                    . ' but no original counterpart found',
                );
            }

            if ($docblock_info->templates) {
                $storage->template_types = [];

                usort(
                    $docblock_info->templates,
                    static fn(array $l, array $r): int => $l[4] > $r[4] ? 1 : -1,
                );

                foreach ($docblock_info->templates as $i => $template_map) {
                    $template_name = $template_map[0];

                    if ($template_map[1] !== null && $template_map[2] !== null) {
                        if (trim($template_map[2])) {
                            $type_string = $template_map[2];
                            try {
                                $type_string = CommentAnalyzer::splitDocLine($type_string)[0];
                            } catch (DocblockParseException $e) {
                                $storage->docblock_issues[] = new InvalidDocblock(
                                    $e->getMessage() . ' in docblock for ' . Interner::str($fq_classlike_name),
                                    $name_location ?? $class_location,
                                );
                                continue;
                            }
                            $type_string = CommentAnalyzer::sanitizeDocblockType($type_string);
                            try {
                                $template_type = TypeParser::parseTokens(
                                    TypeTokenizer::getFullyQualifiedTokens(
                                        $type_string,
                                        $this->aliases,
                                        $storage->template_types,
                                        $this->type_aliases,
                                    ),
                                    null,
                                    $storage->template_types,
                                    $this->type_aliases,
                                );
                            } catch (TypeParseTreeException $e) {
                                $storage->docblock_issues[] = new InvalidDocblock(
                                    $e->getMessage() . ' in docblock for ' . Interner::str($fq_classlike_name),
                                    $name_location ?? $class_location,
                                );

                                continue;
                            }

                            $storage->template_types[$template_name] = [
                                $fq_classlike_name => $template_type,
                            ];
                        } else {
                            $storage->docblock_issues[] = new InvalidDocblock(
                                'Template missing as type',
                                $name_location ?? $class_location,
                            );
                        }
                    } else {
                        /** @psalm-suppress PropertyTypeCoercion due to a Psalm bug */
                        $storage->template_types[$template_name][$fq_classlike_name] = Type::getMixed();
                    }

                    $storage->template_covariants[$i] = $template_map[3];
                }

                $this->class_template_types = $storage->template_types;
            }

            foreach ($docblock_info->template_extends as $extended_class_name) {
                $this->extendTemplatedType($storage, $node, $extended_class_name);
            }

            foreach ($docblock_info->template_implements as $implemented_class_name) {
                $this->implementTemplatedType($storage, $node, $implemented_class_name);
            }

            if ($docblock_info->yield) {
                try {
                    $yield_type_tokens = TypeTokenizer::getFullyQualifiedTokens(
                        $docblock_info->yield,
                        $this->aliases,
                        $storage->template_types,
                        $this->type_aliases,
                    );

                    $yield_type = TypeParser::parseTokens(
                        $yield_type_tokens,
                        null,
                        $storage->template_types ?: [],
                        $this->type_aliases,
                        true,
                    );
                    /** @psalm-suppress UnusedMethodCall */
                    $yield_type->queueClassLikesForScanning(
                        $this->codebase,
                        $this->file_storage,
                        $storage->template_types ?: [],
                    );

                    $storage->yield = $yield_type;
                } catch (TypeParseTreeException) {
                    // do nothing
                }
            }

            if ($docblock_info->extension_requirement !== null) {
                $storage->extension_requirement = Interner::intern((string) TypeParser::parseTokens(
                    TypeTokenizer::getFullyQualifiedTokens(
                        $docblock_info->extension_requirement,
                        $this->aliases,
                        $this->class_template_types,
                        $this->type_aliases,
                    ),
                    null,
                    $this->class_template_types,
                    $this->type_aliases,
                ));
            }

            foreach ($docblock_info->implementation_requirements as $implementation_requirement) {
                $storage->implementation_requirements[] = Interner::intern((string) TypeParser::parseTokens(
                    TypeTokenizer::getFullyQualifiedTokens(
                        $implementation_requirement,
                        $this->aliases,
                        $this->class_template_types,
                        $this->type_aliases,
                    ),
                    null,
                    $this->class_template_types,
                    $this->type_aliases,
                ));
            }

            $storage->sealed_properties = $docblock_info->sealed_properties;
            $storage->sealed_methods = $docblock_info->sealed_methods;


            if ($docblock_info->inheritors) {
                try {
                    $storage->inheritors = TypeParser::parseTokens(
                        TypeTokenizer::getFullyQualifiedTokens(
                            $docblock_info->inheritors,
                            $storage->aliases,
                            $storage->template_types ?? [],
                            $storage->type_aliases,
                            $fq_classlike_name,
                        ),
                        null,
                        $storage->template_types ?? [],
                        $storage->type_aliases,
                        true,
                    );
                } catch (TypeParseTreeException $e) {
                    $storage->docblock_issues[] = new InvalidDocblock(
                        '@psalm-inheritors contains invalid reference:' . $e->getMessage(),
                        $name_location ?? $class_location,
                    );
                }
            }


            if ($docblock_info->properties) {
                foreach ($docblock_info->properties as $property) {
                    $pseudo_property_type_tokens = TypeTokenizer::getFullyQualifiedTokens(
                        $property['type'],
                        $this->aliases,
                        $this->class_template_types,
                        $this->type_aliases,
                        $fq_classlike_name,
                        $storage->parent_class,
                    );

                    try {
                        $pseudo_property_type = TypeParser::parseTokens(
                            $pseudo_property_type_tokens,
                            null,
                            $this->class_template_types,
                            $this->type_aliases,
                            true,
                        );
                        /** @psalm-suppress UnusedMethodCall */
                        $pseudo_property_type->queueClassLikesForScanning(
                            $this->codebase,
                            $this->file_storage,
                            $storage->template_types ?: [],
                        );

                        if ($property['tag'] !== 'property-read' && $property['tag'] !== 'psalm-property-read') {
                            $storage->pseudo_property_set_types[$property['name']] = $pseudo_property_type;
                        }

                        if ($property['tag'] !== 'property-write' && $property['tag'] !== 'psalm-property-write') {
                            $storage->pseudo_property_get_types[$property['name']] = $pseudo_property_type;
                        }
                    } catch (TypeParseTreeException $e) {
                        $storage->docblock_issues[] = new InvalidDocblock(
                            $e->getMessage() . ' in docblock for ' . Interner::str($fq_classlike_name),
                            $name_location ?? $class_location,
                        );
                    }
                }
            }

            foreach ($docblock_info->methods as $method) {
                $functionlike_node_scanner = new FunctionLikeNodeScanner(
                    $this->codebase,
                    $this->file_scanner,
                    $this->file_storage,
                    $this->aliases,
                    $this->type_aliases,
                    $this->storage,
                    [],
                );

                /** @var MethodStorage */
                $pseudo_method_storage = $functionlike_node_scanner->start($method, true);
                $method_name = Interner::intern($method->name->name);

                if ($pseudo_method_storage->is_static) {
                    $storage->pseudo_static_methods[$method_name] = $pseudo_method_storage;
                } else {
                    $storage->pseudo_methods[$method_name] = $pseudo_method_storage;
                    $storage->declaring_pseudo_method_ids[$method_name] = new MethodIdentifier(
                        $fq_classlike_name,
                        $method_name,
                    );
                }
            }


            $storage->deprecated = $docblock_info->deprecated;

            if (count($docblock_info->psalm_internal) !== 0) {
                $storage->internal = $docblock_info->psalm_internal;
            } elseif ($docblock_info->internal && $this->aliases->namespace !== null) {
                $storage->internal = [NamespaceAnalyzer::getNameSpaceRoot($this->aliases->namespace)];
            }

            if ($docblock_info->final && !$storage->final) {
                $storage->final = true;
                $storage->final_from_docblock = true;
            }

            $storage->preserve_constructor_signature = $docblock_info->consistent_constructor;

            if ($storage->preserve_constructor_signature) {
                $has_constructor = false;

                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
                        && $stmt->name->name === '__construct'
                    ) {
                        $has_constructor = true;
                        break;
                    }
                }

                if (!$has_constructor) {
                    self::registerEmptyConstructor($storage);
                }
            }

            $storage->enforce_template_inheritance = $docblock_info->consistent_templates;

            foreach ($docblock_info->mixins as $key => $mixin) {
                $mixin_type = TypeParser::parseTokens(
                    TypeTokenizer::getFullyQualifiedTokens(
                        $mixin,
                        $this->aliases,
                        $this->class_template_types,
                        $this->type_aliases,
                        $fq_classlike_name,
                    ),
                    null,
                    $this->class_template_types,
                    $this->type_aliases,
                    true,
                );

                /** @psalm-suppress UnusedMethodCall */
                $mixin_type->queueClassLikesForScanning(
                    $this->codebase,
                    $this->file_storage,
                    $storage->template_types ?: [],
                );

                if ($mixin_type->isSingle()) {
                    $mixin_type = $mixin_type->getSingleAtomic();

                    if ($mixin_type instanceof TNamedObject) {
                        $storage->namedMixins[] = $mixin_type;
                    }

                    if ($mixin_type instanceof TTemplateParam) {
                        $storage->templatedMixins[] = $mixin_type;
                    }
                }

                if ($key === 0) {
                    $storage->mixin_declaring_fqcln = $storage->name;
                }
            }

            $storage->allowed_mutations = min(
                $docblock_info->allowed_mutations,
                $storage->allowed_mutations,
            );
            $storage->has_mutations_annotation = $docblock_info->has_mutations_annotation;
            $storage->specialize_instance = $docblock_info->taint_specialize;

            $storage->override_property_visibility = $docblock_info->override_property_visibility;
            $storage->override_method_visibility = $docblock_info->override_method_visibility;

            $storage->suppressed_issues = $docblock_info->suppressed_issues;

            if ($docblock_info->description) {
                $storage->description = $docblock_info->description;
            }

            $storage->public_api = $docblock_info->public_api;
        }

        foreach ($node->stmts as $node_stmt) {
            if ($node_stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $this->visitClassConstDeclaration($node_stmt, $storage, $fq_classlike_name);
            } elseif ($node_stmt instanceof PhpParser\Node\Stmt\EnumCase
                && $node instanceof PhpParser\Node\Stmt\Enum_
            ) {
                $this->visitEnumDeclaration(
                    $node_stmt,
                    $storage,
                    $fq_classlike_name,
                );
            }
        }

        if ($storage->is_enum) {
            $name_types = [];
            $values_types = [];
            foreach ($storage->enum_cases as $name => $enum_case_storage) {
                $name_types[] = Type::getAtomicStringFromLiteral(Interner::str($name));
                if ($storage->enum_type !== null
                    && $enum_case_storage->value !== null) {
                    if ($enum_case_storage->value instanceof UnresolvedConstantComponent) {
                        // backed enum with a type yet unknown
                        $values_types[] = new Type\Atomic\TMixed;
                    } else {
                        $values_types[] = $enum_case_storage->value;
                    }
                }
            }
            if ($name_types !== []) {
                $storage->declaring_property_ids[StrId::name] = $storage->name;
                $storage->appearing_property_ids[StrId::name] = $storage->name;
                $storage->properties[StrId::name] = new PropertyStorage();
                $storage->properties[StrId::name]->type = new Union($name_types);
            }
            if ($values_types !== []) {
                $storage->declaring_property_ids[StrId::value] = $storage->name;
                $storage->appearing_property_ids[StrId::value] = $storage->name;
                $storage->properties[StrId::value] = new PropertyStorage();
                $storage->properties[StrId::value]->type = new Union($values_types);
            }
        }

        foreach ($node->stmts as $node_stmt) {
            if ($node_stmt instanceof PhpParser\Node\Stmt\Property) {
                $this->visitPropertyDeclaration($node_stmt, $this->config, $storage, $fq_classlike_name);
            }
        }

        foreach ($node->attrGroups as $attr_group) {
            foreach ($attr_group->attrs as $attr) {
                $attribute = AttributeResolver::resolve(
                    $this->codebase,
                    $this->file_scanner,
                    $this->file_storage,
                    $this->aliases,
                    $attr,
                    $this->storage->name ?? null,
                );

                if ($attribute->fq_class_name === StrId::Psalm_Deprecated
                    || $attribute->fq_class_name === StrId::JetBrains_PhpStorm_Deprecated
                    || $attribute->fq_class_name === StrId::Deprecated
                ) {
                    $storage->deprecated = true;
                }

                if ($attribute->fq_class_name === StrId::Psalm_Internal && !$storage->internal) {
                    $storage->internal = [NamespaceAnalyzer::getNameSpaceRoot($fq_classlike_name)];
                }

                if ($attribute->fq_class_name === StrId::Psalm_Immutable
                    || $attribute->fq_class_name === StrId::JetBrains_PhpStorm_Immutable
                ) {
                    $storage->allowed_mutations = min(
                        Mutations::LEVEL_INTERNAL_READ,
                        $storage->allowed_mutations,
                    );
                    $storage->has_mutations_annotation = true;
                }

                if ($attribute->fq_class_name === StrId::Psalm_ExternalMutationFree) {
                    $storage->allowed_mutations = min(
                        Mutations::LEVEL_INTERNAL_READ_WRITE,
                        $storage->allowed_mutations,
                    );
                    $storage->has_mutations_annotation = true;
                }

                if ($attribute->fq_class_name === StrId::AllowDynamicProperties && $storage->readonly) {
                    IssueBuffer::maybeAdd(new InvalidAttribute(
                        'Readonly classes cannot have dynamic properties',
                        new CodeLocation($this->file_scanner, $attr, null, true),
                    ));
                    continue;
                }

                $storage->attributes[] = $attribute;
            }
        }

        return null;
    }

    public function finish(PhpParser\Node\Stmt\ClassLike $node): ClassLikeStorage
    {
        if (!$this->storage) {
            throw new UnexpectedValueException(
                'Storage should exist in ' . $this->file_path . ' at ' . $node->getLine(),
            );
        }

        $classlike_storage = $this->storage;

        $fq_classlike_name = $classlike_storage->name;

        $mapped_properties = PropertyMap::getPropertiesForClass($fq_classlike_name);

        if ($mapped_properties !== null) {
            foreach ($mapped_properties as $property_name => $public_mapped_property) {
                $property_type = Type::parseString($public_mapped_property);

                /** @psalm-suppress UnusedMethodCall */
                $property_type->queueClassLikesForScanning($this->codebase, $this->file_storage);

                if (!isset($classlike_storage->properties[$property_name])) {
                    $classlike_storage->properties[$property_name] = new PropertyStorage();
                }

                if ($fq_classlike_name === StrId::DateInterval && $property_name === StrId::days) {
                    /** @psalm-suppress InaccessibleProperty We just parsed this type */
                    $property_type->ignore_falsable_issues = true;
                }

                $classlike_storage->properties[$property_name]->type = $property_type;

                $classlike_storage->declaring_property_ids[$property_name] = $fq_classlike_name;
                $classlike_storage->appearing_property_ids[$property_name] = $fq_classlike_name;
            }
        }

        $converted_aliases = [];
        foreach ($this->classlike_type_aliases as $key => $type) {
            try {
                $union = TypeParser::parseTokens(
                    $type->replacement_tokens,
                    null,
                    [],
                    $this->type_aliases,
                    true,
                );

                $converted_aliases[$key] = new ClassTypeAlias(array_values($union->getAtomicTypes()));
            } catch (TypeParseTreeException $e) {
                $classlike_storage->docblock_issues[] = new InvalidDocblock(
                    '@psalm-type ' . Interner::str($key) . ' contains invalid reference: ' . $e->getMessage(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );
            } catch (Exception) {
                $classlike_storage->docblock_issues[] = new InvalidDocblock(
                    '@psalm-type ' . Interner::str($key) . ' contains invalid references',
                    new CodeLocation($this->file_scanner, $node, null, true),
                );
            }
        }

        $classlike_storage->type_aliases = $converted_aliases;

        return $classlike_storage;
    }

    public function handleTraitUse(PhpParser\Node\Stmt\TraitUse $node): void
    {
        $storage = $this->storage;

        if (!$storage) {
            throw new UnexpectedValueException('bad');
        }

        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                $old_name = Interner::intern($adaptation->method->name);
                $new_name = $old_name;

                if ($adaptation->newName) {
                    $new_name = Interner::intern($adaptation->newName->name);

                    if ($new_name !== $old_name) {
                        $storage->trait_alias_map[$new_name] = $old_name;
                        $storage->trait_alias_map_cased[$new_name] = $old_name;
                    }
                }

                if ($adaptation->newModifier) {
                    switch ($adaptation->newModifier) {
                        case 1:
                            $storage->trait_visibility_map[$new_name] = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
                            break;

                        case 2:
                            $storage->trait_visibility_map[$new_name] = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
                            break;

                        case 4:
                            $storage->trait_visibility_map[$new_name] = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
                            break;

                        case 32:
                            $storage->trait_final_map[$new_name] = true;
                            break;
                    }
                }
            }
        }

        foreach ($node->traits as $trait) {
            $trait_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($trait, $this->aliases);
            $this->codebase->scanner->queueClassLikeForScanning($trait_fqcln, $this->file_scanner->will_analyze);
            $storage->used_traits[$trait_fqcln] = $trait_fqcln;
            $this->file_storage->required_classes[$trait_fqcln] = $trait_fqcln;
        }

        if ($node_comment = $node->getDocComment()) {
            $comments = DocComment::parsePreservingLength($node_comment);

            if (isset($comments->combined_tags['use'])) {
                foreach ($comments->combined_tags['use'] as $template_line) {
                    $this->useTemplatedType(
                        $storage,
                        $node,
                        CommentAnalyzer::sanitizeDocblockType($template_line),
                    );
                }
            }

            if (isset($comments->tags['template-extends'])
                || isset($comments->tags['extends'])
                || isset($comments->tags['template-implements'])
                || isset($comments->tags['implements'])
            ) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    'You must use @use or @template-use to parameterize traits',
                    new CodeLocation($this->file_scanner, $node, null, true),
                );
            }
        }
    }

    private function extendTemplatedType(
        ClassLikeStorage $storage,
        PhpParser\Node\Stmt\ClassLike $node,
        string $extended_class_name,
    ): void {
        if (trim($extended_class_name) === '') {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Extended class cannot be empty in docblock for ' . Interner::str($storage->name),
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        try {
            $extended_union_type = TypeParser::parseTokens(
                TypeTokenizer::getFullyQualifiedTokens(
                    $extended_class_name,
                    $this->aliases,
                    $this->class_template_types,
                    $this->type_aliases,
                ),
                null,
                $this->class_template_types,
                $this->type_aliases,
                true,
            );
        } catch (TypeParseTreeException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . Interner::str($storage->name),
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        if (!$extended_union_type->isSingle()) {
            $storage->docblock_issues[] = new InvalidDocblock(
                '@template-extends cannot be a union type',
                new CodeLocation($this->file_scanner, $node, null, true),
            );
        }

        /** @psalm-suppress UnusedMethodCall */
        $extended_union_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $storage->template_types ?: [],
        );

        foreach ($extended_union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TGenericObject) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-extends has invalid class ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );

                return;
            }

            $generic_class = $atomic_type->value;

            if (!isset($storage->parent_classes[$generic_class])
                && !isset($storage->parent_interfaces[$generic_class])
            ) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-extends must include the name of an extended class,'
                        . ' got ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );
            }

            $extended_type_parameters = [];

            $storage->template_type_extends_count[$atomic_type->value] = count($atomic_type->type_params);

            foreach ($atomic_type->type_params as $type_param) {
                $extended_type_parameters[] = $type_param;
            }

            $storage->template_extended_offsets[$atomic_type->value] = $extended_type_parameters;
        }
    }

    private function implementTemplatedType(
        ClassLikeStorage $storage,
        PhpParser\Node\Stmt\ClassLike $node,
        string $implemented_class_name,
    ): void {
        if (trim($implemented_class_name) === '') {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Extended class cannot be empty in docblock for ' . Interner::str($storage->name),
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        try {
            $implemented_union_type = TypeParser::parseTokens(
                TypeTokenizer::getFullyQualifiedTokens(
                    $implemented_class_name,
                    $this->aliases,
                    $this->class_template_types,
                    $this->type_aliases,
                ),
                null,
                $this->class_template_types,
                $this->type_aliases,
                true,
            );
        } catch (TypeParseTreeException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . Interner::str($storage->name),
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        if (!$implemented_union_type->isSingle()) {
            $storage->docblock_issues[] = new InvalidDocblock(
                '@template-implements cannot be a union type',
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        /** @psalm-suppress UnusedMethodCall */
        $implemented_union_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $storage->template_types ?: [],
        );

        foreach ($implemented_union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TGenericObject) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-implements has invalid class ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );

                return;
            }

            $generic_class = $atomic_type->value;

            if (!isset($storage->class_implements[$generic_class])) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-implements must include the name of an implemented class,'
                        . ' got ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );

                return;
            }

            $implemented_type_parameters = [];

            $storage->template_type_implements_count[$generic_class] = count($atomic_type->type_params);

            foreach ($atomic_type->type_params as $type_param) {
                $implemented_type_parameters[] = $type_param;
            }

            $storage->template_extended_offsets[$atomic_type->value] = $implemented_type_parameters;
        }
    }

    private function useTemplatedType(
        ClassLikeStorage $storage,
        PhpParser\Node\Stmt\TraitUse $node,
        string $used_class_name,
    ): void {
        if (trim($used_class_name) === '') {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Extended class cannot be empty in docblock for ' . Interner::str($storage->name),
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        try {
            $used_union_type = TypeParser::parseTokens(
                TypeTokenizer::getFullyQualifiedTokens(
                    $used_class_name,
                    $this->aliases,
                    $this->class_template_types,
                    $this->type_aliases,
                ),
                null,
                $this->class_template_types,
                $this->type_aliases,
                true,
            );
        } catch (TypeParseTreeException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . Interner::str($storage->name),
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        if (!$used_union_type->isSingle()) {
            $storage->docblock_issues[] = new InvalidDocblock(
                '@template-use cannot be a union type',
                new CodeLocation($this->file_scanner, $node, null, true),
            );

            return;
        }

        /** @psalm-suppress UnusedMethodCall */
        $used_union_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $storage->template_types ?: [],
        );

        foreach ($used_union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TGenericObject) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-use has invalid class ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );

                return;
            }

            $generic_class = $atomic_type->value;

            if (!isset($storage->used_traits[$generic_class])) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-use must include the name of an used class,'
                        . ' got ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true),
                );

                return;
            }

            $used_type_parameters = [];

            $storage->template_type_uses_count[$generic_class] = count($atomic_type->type_params);

            foreach ($atomic_type->type_params as $type_param) {
                $used_type_parameters[] = $type_param->replaceClassLike(StrId::self, $storage->name);
            }

            $storage->template_extended_offsets[$atomic_type->value] = $used_type_parameters;
        }
    }

    private static function registerEmptyConstructor(ClassLikeStorage $class_storage): void
    {
        if (isset($class_storage->methods[StrId::__construct])) {
            return;
        }

        $storage = $class_storage->methods[StrId::__construct] = new MethodStorage();

        $storage->cased_name = StrId::__construct;
        $storage->defining_fqcln = $class_storage->name;

        $storage->allowed_mutations = Mutations::LEVEL_NONE;
        $storage->mutation_free_assumed = true;

        $class_storage->declaring_method_ids[StrId::__construct] = new MethodIdentifier(
            $class_storage->name,
            StrId::__construct,
        );

        $class_storage->inheritable_method_ids[StrId::__construct]
            = $class_storage->declaring_method_ids[StrId::__construct];
        $class_storage->appearing_method_ids[StrId::__construct]
            = $class_storage->declaring_method_ids[StrId::__construct];
        $class_storage->overridden_method_ids[StrId::__construct] = [];

        $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
    }

    private function visitClassConstDeclaration(
        PhpParser\Node\Stmt\ClassConst $stmt,
        ClassLikeStorage $storage,
        int $fq_classlike_name,
    ): void {
        if ($storage->is_trait && $this->codebase->analysis_php_version_id < 8_02_00) {
            IssueBuffer::maybeAdd(new ConstantDeclarationInTrait(
                'Traits cannot declare constants until PHP 8.2.0',
                new CodeLocation($this->file_scanner, $stmt),
            ));
            return;
        }

        $existing_constants = $storage->constants;

        $comment = $stmt->getDocComment();
        $var_comment = null;
        $deprecated = false;
        $description = null;
        $config = $this->config;

        if ($comment && $comment->getText() && ($config->use_docblock_types || $config->use_docblock_property_types)) {
            $comments = DocComment::parsePreservingLength($comment);

            if (isset($comments->tags['deprecated'])) {
                $deprecated = true;
            }

            $description = $comments->description;

            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $this->codebase,
                    $comment,
                    $this->file_scanner,
                    $this->aliases,
                    [],
                    $this->type_aliases,
                );

                $var_comment = array_pop($var_comments);
            } catch (IncorrectDocblockException $e) {
                $storage->docblock_issues[] = new MissingDocblockType(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $stmt, null, true),
                );
            } catch (DocblockParseException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $stmt, null, true),
                );
            }
        }

        foreach ($stmt->consts as $const) {
            $const_name = Interner::intern($const->name->name);
            if (isset($storage->constants[$const_name])
                || isset($storage->enum_cases[$const_name])
            ) {
                IssueBuffer::maybeAdd(new DuplicateConstant(
                    'Constant names should be unique',
                    new CodeLocation($this->file_scanner, $const),
                    $fq_classlike_name,
                ));
                continue;
            }

            $inferred_type = SimpleTypeInferer::infer(
                $this->codebase,
                new NodeDataProvider(),
                $const->value,
                $this->aliases,
                null,
                $existing_constants,
                $fq_classlike_name,
            );

            $type_location = null;
            if ($var_comment && $var_comment->type !== null) {
                $const_type = $var_comment->type;

                if ($var_comment->type_start !== null
                    && $var_comment->type_end !== null
                    && $var_comment->line_number !== null
                ) {
                    $type_location = new DocblockTypeLocation(
                        $this->file_scanner,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number,
                    );
                }
            } else {
                $const_type = $inferred_type;
            }
            $suppressed_issues = $var_comment ? $var_comment->suppressed_issues : [];

            $attributes = [];
            foreach ($stmt->attrGroups as $attr_group) {
                foreach ($attr_group->attrs as $attr) {
                    $attributes[] = $attr = AttributeResolver::resolve(
                        $this->codebase,
                        $this->file_scanner,
                        $this->file_storage,
                        $this->aliases,
                        $attr,
                        $this->storage->name ?? null,
                    );

                    if ($attr->fq_class_name === StrId::Psalm_Deprecated
                        || $attr->fq_class_name === StrId::JetBrains_PhpStorm_Deprecated
                        || $attr->fq_class_name === StrId::Deprecated
                    ) {
                        $deprecated = true;
                    }
                }
            }
            $unresolved_node = null;
            if ($inferred_type
                && !(
                    $const->value instanceof Concat
                    && $inferred_type->isSingle()
                    && $inferred_type->getSingleAtomic()::class === TString::class
                )
            ) {
                $exists = true;
            } else {
                $exists = false;
                $unresolved_const_expr = ExpressionResolver::getUnresolvedClassConstExpr(
                    $const->value,
                    $this->aliases,
                    $fq_classlike_name,
                    $storage->parent_class,
                );

                if ($unresolved_const_expr) {
                    $unresolved_node = $unresolved_const_expr;
                } else {
                    $const_type = Type::getMixed();
                }
            }
            $storage->constants[$const_name] = $constant_storage = new ClassConstantStorage(
                $const_type,
                $inferred_type,
                $stmt->isProtected()
                    ? ClassLikeAnalyzer::VISIBILITY_PROTECTED
                    : ($stmt->isPrivate()
                        ? ClassLikeAnalyzer::VISIBILITY_PRIVATE
                        : ClassLikeAnalyzer::VISIBILITY_PUBLIC),
                new CodeLocation(
                    $this->file_scanner,
                    $const->name,
                ),
                $type_location,
                new CodeLocation(
                    $this->file_scanner,
                    $const,
                ),
                $deprecated,
                $stmt->isFinal(),
                $unresolved_node,
                $attributes,
                $suppressed_issues,
                $description,
            );

            if ($this->codebase->analysis_php_version_id >= 8_03_00
                && !$storage->final
                && $stmt->type === null
            ) {
                IssueBuffer::maybeAdd(
                    new MissingClassConstType(
                        sprintf(
                            'Class constant "%s::%s" should have a declared type.',
                            Interner::str($storage->name),
                            $const->name->name,
                        ),
                        new CodeLocation($this->file_scanner, $const),
                    ),
                    $suppressed_issues,
                );
            }

            if ($exists) {
                $existing_constants[$const_name] = $constant_storage;
            }
        }
    }

    private function visitEnumDeclaration(
        PhpParser\Node\Stmt\EnumCase $stmt,
        ClassLikeStorage $storage,
        int $fq_classlike_name,
    ): void {
        $case_name = Interner::intern($stmt->name->name);

        if (isset($storage->constants[$case_name])) {
            IssueBuffer::maybeAdd(new DuplicateConstant(
                'Constant names should be unique',
                new CodeLocation($this->file_scanner, $stmt),
                $fq_classlike_name,
            ));
            return;
        }

        $enum_value = null;

        $case_location = new CodeLocation($this->file_scanner, $stmt);

        if ($stmt->expr !== null) {
            $case_type = SimpleTypeInferer::infer(
                $this->codebase,
                new NodeDataProvider(),
                $stmt->expr,
                $this->aliases,
                $this->file_scanner,
                $storage->constants,
                $fq_classlike_name,
            );

            if ($case_type) {
                if ($case_type->isSingleIntLiteral()) {
                    $enum_value = $case_type->getSingleIntLiteral();
                } elseif ($case_type->isSingleStringLiteral()) {
                    $enum_value = $case_type->getSingleStringLiteral();
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidEnumCaseValue(
                            'Case of a backed enum should have either string or int value',
                            $case_location,
                            $fq_classlike_name,
                        ),
                    );
                }
            } else {
                $enum_value = ExpressionResolver::getUnresolvedClassConstExpr(
                    $stmt->expr,
                    $this->aliases,
                    $fq_classlike_name,
                    $storage->parent_class,
                );
            }
        }


        if (!isset($storage->enum_cases[$case_name])) {
            $deprecated = false;

            $attrs = $this->getAttributeStorageFromStatement(
                $this->codebase,
                $this->file_scanner,
                $this->file_storage,
                $this->aliases,
                $stmt,
                $this->storage->name ?? null,
            );

            foreach ($attrs as $attribute) {
                if ($attribute->fq_class_name === StrId::Psalm_Deprecated
                    || $attribute->fq_class_name === StrId::JetBrains_PhpStorm_Deprecated
                    || $attribute->fq_class_name === StrId::Deprecated
                ) {
                    $deprecated = true;
                    break;
                }
            }

            $comment = $stmt->getDocComment();
            if ($comment) {
                $comments = DocComment::parsePreservingLength($comment);

                if (isset($comments->tags['deprecated'])) {
                    $deprecated = true;
                }
            }
            $storage->enum_cases[$case_name] = new EnumCaseStorage(
                $enum_value,
                $case_location,
                $deprecated,
            );
        } else {
            IssueBuffer::maybeAdd(
                new DuplicateEnumCase(
                    'Enum case names should be unique',
                    $case_location,
                    $fq_classlike_name,
                ),
            );
        }
    }

    /**
     * @param PhpParser\Node\Stmt\Property|PhpParser\Node\Stmt\EnumCase $stmt
     * @return list<AttributeStorage>
     */
    private function getAttributeStorageFromStatement(
        Codebase $codebase,
        FileScanner $file_scanner,
        FileStorage $file_storage,
        Aliases $aliases,
        PhpParser\Node\Stmt $stmt,
        ?int $fq_classlike_name,
    ): array {
        $storages = [];
        foreach ($stmt->attrGroups as $attr_group) {
            foreach ($attr_group->attrs as $attr) {
                $storages[] = AttributeResolver::resolve(
                    $codebase,
                    $file_scanner,
                    $file_storage,
                    $aliases,
                    $attr,
                    $fq_classlike_name,
                );
            }
        }
        return $storages;
    }

    private function visitPropertyDeclaration(
        PhpParser\Node\Stmt\Property $stmt,
        Config $config,
        ClassLikeStorage $storage,
        int $fq_classlike_name,
    ): void {
        $comment = $stmt->getDocComment();
        $var_comment = null;

        $property_is_initialized = false;

        $existing_constants = $storage->constants;

        if ($comment && $comment->getText() && ($config->use_docblock_types || $config->use_docblock_property_types)) {
            if (preg_match('/[ \t\*]+@psalm-suppress[ \t]+PropertyNotSetInConstructor/', (string)$comment)) {
                $property_is_initialized = true;
            }

            if (preg_match('/[ \t\*]+@property[ \t]+/', (string)$comment)) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@property is valid only in docblocks for class',
                    new CodeLocation($this->file_scanner, $stmt, null, true),
                );
            }

            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $this->codebase,
                    $comment,
                    $this->file_scanner,
                    $this->aliases,
                    !$stmt->isStatic() ? $this->class_template_types : [],
                    $this->type_aliases,
                );

                $var_comment = array_pop($var_comments);
            } catch (IncorrectDocblockException $e) {
                $storage->docblock_issues[] = new MissingDocblockType(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $stmt, null, true),
                );
            } catch (DocblockParseException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $stmt, null, true),
                );
            }
        }

        $signature_type = null;
        $signature_type_location = null;

        if ($stmt->type) {
            $parser_property_type = $stmt->type;
            /** @var Identifier|IntersectionType|Name|NullableType|UnionType $parser_property_type */

            $signature_type_location = new CodeLocation(
                $this->file_scanner,
                $parser_property_type,
                null,
                false,
                CodeLocation::FUNCTION_RETURN_TYPE,
            );

            $signature_type = TypeHintResolver::resolve(
                $parser_property_type,
                $signature_type_location,
                $this->codebase,
                $this->file_storage,
                $this->storage,
                $this->aliases,
                $this->codebase->analysis_php_version_id,
            );
        }

        $doc_var_group_type = $var_comment->type ?? null;

        if ($doc_var_group_type) {
            /** @psalm-suppress UnusedMethodCall */
            $doc_var_group_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
        }

        foreach ($stmt->props as $property) {
            $doc_var_location = null;

            $property_name = Interner::intern($property->name->name);
            $property_id = new PropertyIdentifier($fq_classlike_name, $property_name);

            if (isset($storage->properties[$property_name])) {
                IssueBuffer::maybeAdd(
                    new DuplicateProperty(
                        'Property ' . (string) $property_id . ' has already been defined',
                        new CodeLocation($this->file_scanner, $stmt, null, true),
                        $property_id,
                    ),
                );
            }

            $property_storage = $storage->properties[$property_name] = new PropertyStorage();
            $property_storage->is_static = $stmt->isStatic();
            $property_storage->type = $signature_type;
            $property_storage->signature_type = $signature_type;
            $property_storage->signature_type_location = $signature_type_location;
            $property_storage->type_location = $signature_type_location;
            $property_storage->location = new CodeLocation($this->file_scanner, $property->name);
            $property_storage->stmt_location = new CodeLocation($this->file_scanner, $stmt);
            $property_storage->has_default = (bool)$property->default;
            $property_storage->deprecated = $var_comment ? $var_comment->deprecated : false;
            $property_storage->suppressed_issues = $var_comment ? $var_comment->suppressed_issues : [];
            $property_storage->internal = $var_comment ? $var_comment->psalm_internal : [];
            if (count($property_storage->internal) === 0 && $var_comment && $var_comment->internal) {
                $property_storage->internal = [NamespaceAnalyzer::getNameSpaceRoot($fq_classlike_name)];
            }
            $property_storage->readonly = $storage->readonly
                || $stmt->isReadonly()
                || ($var_comment && $var_comment->readonly);
            $property_storage->allow_private_mutation = $var_comment ? $var_comment->allow_private_mutation : false;
            $property_storage->description = $var_comment ? $var_comment->description : null;

            if (!$signature_type && $storage->readonly) {
                IssueBuffer::maybeAdd(
                    new MissingPropertyType(
                        'Properties of readonly classes must have a type',
                        new CodeLocation($this->file_scanner, $stmt, null, true),
                        $property_id,
                    ),
                );
            }

            if (!$signature_type && !$doc_var_group_type) {
                if ($property->default) {
                    $property_storage->suggested_type = SimpleTypeInferer::infer(
                        $this->codebase,
                        new NodeDataProvider(),
                        $property->default,
                        $this->aliases,
                        null,
                        $existing_constants,
                        $fq_classlike_name,
                    );
                }

                $property_storage->type = null;
            } else {
                if ($var_comment
                    && $var_comment->type_start
                    && $var_comment->type_end
                    && $var_comment->line_number
                ) {
                    $doc_var_location = new DocblockTypeLocation(
                        $this->file_scanner,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number,
                    );
                }

                if ($doc_var_group_type) {
                    $property_storage->type = $doc_var_group_type;
                }
            }

            if ($property_storage->type
                && $property_storage->type !== $property_storage->signature_type
            ) {
                if (!$property_storage->signature_type) {
                    $property_storage->type_location = $doc_var_location;
                }

                if ($property_storage->signature_type) {
                    $all_typehint_types_match = true;
                    $signature_atomic_types = $property_storage->signature_type->getAtomicTypes();

                    foreach ($property_storage->type->getAtomicTypes() as $key => $type) {
                        if (isset($signature_atomic_types[$key])) {
                            /** @psalm-suppress InaccessibleProperty We just created this type */
                            $type->from_docblock = false;
                        } else {
                            $all_typehint_types_match = false;
                        }
                    }

                    if ($all_typehint_types_match) {
                        /** @psalm-suppress InaccessibleProperty We just created this type */
                        $property_storage->type->from_docblock = false;
                    }

                    if ($property_storage->signature_type->isNullable()
                        && !$property_storage->type->isNullable()
                    ) {
                        $property_storage->type = $property_storage->type->getBuilder()->addType(new TNull())->freeze();
                    }
                }

                /** @psalm-suppress UnusedMethodCall */
                $property_storage->type->queueClassLikesForScanning($this->codebase, $this->file_storage);
            }

            if ($stmt->isPublic()) {
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
            } elseif ($stmt->isProtected()) {
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            } elseif ($stmt->isPrivate()) {
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
            }

            $storage->declaring_property_ids[$property_name] = $fq_classlike_name;
            $storage->appearing_property_ids[$property_name] = $fq_classlike_name;

            if ($property_is_initialized) {
                $storage->initialized_properties[$property_name] = true;
            }

            if (!$stmt->isPrivate()) {
                $storage->inheritable_property_ids[$property_name] = $fq_classlike_name;
            }

            $attrs = $this->getAttributeStorageFromStatement(
                $this->codebase,
                $this->file_scanner,
                $this->file_storage,
                $this->aliases,
                $stmt,
                $this->storage->name ?? null,
            );

            foreach ($attrs as $attribute) {
                if ($attribute->fq_class_name === StrId::Psalm_Deprecated
                    || $attribute->fq_class_name === StrId::JetBrains_PhpStorm_Deprecated
                    || $attribute->fq_class_name === StrId::Deprecated
                ) {
                    $property_storage->deprecated = true;
                }

                if ($attribute->fq_class_name === StrId::Psalm_Internal && !$property_storage->internal) {
                    $property_storage->internal = [NamespaceAnalyzer::getNameSpaceRoot($fq_classlike_name)];
                }

                if ($attribute->fq_class_name === StrId::Psalm_Readonly) {
                    $property_storage->readonly = true;
                }

                $property_storage->attributes[] = $attribute;
            }

            // Process property hooks
            foreach ($stmt->hooks as $hook) {
                $hook_name = strtolower($hook->name->toString());
                if ($hook_name === 'get'
                    || $hook_name === 'set'
                ) {
                    $hook_storage = new PropertyHookStorage(
                        $hook_name === 'get',
                        $hook->isFinal(),
                        $hook->byRef,
                        new CodeLocation(
                            $this->file_scanner,
                            $hook,
                            null,
                            true,
                        ),
                    );
                    if ($hook_storage->is_get) {
                        $property_storage->hook_get = $hook_storage;
                    } else {
                        $property_storage->hook_set = $hook_storage;
                    }
                } else {
                    $storage->docblock_issues[] = new ParseError(
                        'Property hooks must be either "get" or "set"',
                        new CodeLocation($this->file_scanner, $stmt, null, true),
                    );
                }
            }

            // Validate interface properties
            if ($storage->is_interface && $this->codebase->analysis_php_version_id >= 8_04_00) {
                if (!$property_storage->hook_get && !$property_storage->hook_set) {
                    $storage->docblock_issues[] = new ParseError(
                        'Interface properties must have at least one hook',
                        new CodeLocation($this->file_scanner, $stmt, null, true),
                    );
                }

                if ($stmt->isStatic()) {
                    $storage->docblock_issues[] = new ParseError(
                        'Interface properties cannot be static',
                        new CodeLocation($this->file_scanner, $stmt, null, true),
                    );
                }

                // Interface properties must be explicitly declared as public
                if (!$stmt->isPublic() || ($stmt->flags & PhpParser\Modifiers::VISIBILITY_MASK) === 0) {
                    $storage->docblock_issues[] = new ParseError(
                        'Interface properties must be public',
                        new CodeLocation($this->file_scanner, $stmt, null, true),
                    );
                }
            }
        }
    }

    /**
     * @return array<int, LinkableTypeAlias> alias name id => alias
     */
    private function getImportedTypeAliases(ClassLikeDocblockComment $comment, int $fq_classlike_name): array
    {
        /** @var array<int, LinkableTypeAlias> $results */
        $results = [];

        foreach ($comment->imported_types as $import_type_entry) {
            $imported_type_data = $import_type_entry['parts'];
            $location = new DocblockTypeLocation(
                $this->file_scanner,
                $import_type_entry['start_offset'],
                $import_type_entry['end_offset'],
                $import_type_entry['line_number'],
            );
            // There are two valid forms:
            // @psalm-import Thing from Something
            // @psalm-import Thing from Something as Alias
            // but there could be leftovers after that
            if (count($imported_type_data) < 3) {
                $this->file_storage->docblock_issues[] = new InvalidTypeImport(
                    'Invalid import in docblock for ' . Interner::str($fq_classlike_name)
                    . ', expecting "<TypeName> from <ClassName>",'
                    . ' got "' . implode(' ', $imported_type_data) . '" instead.',
                    $location,
                );
                continue;
            }

            if ($imported_type_data[1] === 'from'
                && !empty($imported_type_data[0])
                && !empty($imported_type_data[2])
            ) {
                $type_alias_name = $as_alias_name = $imported_type_data[0];
                $declaring_classlike_name = $imported_type_data[2];
            } else {
                $this->file_storage->docblock_issues[] = new InvalidTypeImport(
                    'Invalid import in docblock for ' . Interner::str($fq_classlike_name)
                    . ', expecting "<TypeName> from <ClassName>", got "'
                    . implode(
                        ' ',
                        [$imported_type_data[0], $imported_type_data[1], $imported_type_data[2]],
                    ) . '" instead.',
                    $location,
                );
                continue;
            }

            if (count($imported_type_data) >= 4 && $imported_type_data[3] === 'as') {
                // long form
                if (empty($imported_type_data[4])) {
                    $this->file_storage->docblock_issues[] = new InvalidTypeImport(
                        'Invalid import in docblock for ' . Interner::str($fq_classlike_name)
                        . ', expecting "as <TypeName>", got "'
                        . $imported_type_data[3] . ' ' . ($imported_type_data[4] ?? '') . '" instead.',
                        $location,
                    );
                    continue;
                }

                $as_alias_name = $imported_type_data[4];
            }

            $declaring_fq_classlike_name = Type::getFQCLNFromString(
                $declaring_classlike_name,
                $this->aliases,
            );

            $this->codebase->scanner->queueClassLikeForScanning($declaring_fq_classlike_name);
            $this->file_storage->referenced_classlikes[$declaring_fq_classlike_name]
                = $declaring_fq_classlike_name;

            $results[Interner::intern($as_alias_name)] = new LinkableTypeAlias(
                $declaring_fq_classlike_name,
                Interner::intern($type_alias_name),
                $import_type_entry['line_number'],
                $import_type_entry['start_offset'],
                $import_type_entry['end_offset'],
            );
        }

        return $results;
    }

    /**
     * @param  array<int, TypeAlias> $type_aliases alias name id => alias
     * @return array<int, InlineTypeAlias> alias name id => alias
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function getTypeAliasesFromComment(
        PhpParser\Comment\Doc $comment,
        Aliases $aliases,
        ?array $type_aliases,
        ?int $self_fqcln,
    ): array {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        if (!isset($parsed_docblock->tags['psalm-type']) && !isset($parsed_docblock->tags['phpstan-type'])) {
            return [];
        }

        $type_alias_comment_lines = array_merge(
            $parsed_docblock->tags['phpstan-type'] ?? [],
            $parsed_docblock->tags['psalm-type'] ?? [],
        );

        return self::getTypeAliasesFromCommentLines(
            $type_alias_comment_lines,
            $aliases,
            $type_aliases,
            $self_fqcln,
        );
    }

    /**
     * @param array<string>    $type_alias_comment_lines
     * @param array<int, TypeAlias> $type_aliases alias name id => alias
     * @return array<int, InlineTypeAlias> alias name id => alias
     * @throws DocblockParseException if there was a problem parsing the docblock
     * @psalm-external-mutation-free
     */
    private static function getTypeAliasesFromCommentLines(
        array $type_alias_comment_lines,
        Aliases $aliases,
        ?array $type_aliases,
        ?int $self_fqcln,
    ): array {
        $type_alias_tokens = [];

        foreach ($type_alias_comment_lines as $var_line) {
            $var_line = trim($var_line);

            if (!$var_line) {
                continue;
            }

            $var_line_parts = preg_split('/( |=)/', $var_line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            if (!$var_line_parts) {
                continue;
            }

            $type_alias = array_shift($var_line_parts);

            if (!isset($var_line_parts[0])) {
                continue;
            }

            while (isset($var_line_parts[0]) && $var_line_parts[0] === ' ') {
                array_shift($var_line_parts);
            }

            if (!isset($var_line_parts[0])) {
                continue;
            }

            if ($var_line_parts[0] === '=') {
                array_shift($var_line_parts);
            }

            if (!isset($var_line_parts[0])) {
                continue;
            }

            while (isset($var_line_parts[0]) && $var_line_parts[0] === ' ') {
                array_shift($var_line_parts);
            }

            $type_string = implode('', $var_line_parts);
            $type_string = ltrim($type_string, "* \n\r");
            try {
                $type_string = CommentAnalyzer::splitDocLine($type_string)[0];
            } catch (DocblockParseException $e) {
                throw new DocblockParseException($type_string . ' is not a valid type: '.$e->getMessage());
            }
            $type_string = CommentAnalyzer::sanitizeDocblockType($type_string);

            try {
                $type_tokens = TypeTokenizer::getFullyQualifiedTokens(
                    $type_string,
                    $aliases,
                    null,
                    $type_alias_tokens + ($type_aliases ?? []),
                    $self_fqcln,
                );
            } catch (TypeParseTreeException $e) {
                throw new DocblockParseException($type_string . ' is not a valid type: '.$e->getMessage());
            }

            $type_alias_tokens[Interner::intern($type_alias)] = new InlineTypeAlias($type_tokens);
        }

        return $type_alias_tokens;
    }
}
