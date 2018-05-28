<?php
namespace Psalm\Visitor;

use PhpParser;
use Psalm\Aliases;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\Statements\Expression\CallChecker;
use Psalm\Checker\Statements\Expression\IncludeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Codebase;
use Psalm\Codebase\CallMap;
use Psalm\Codebase\PropertyMap;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\FileIncludeException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MisplacedRequiredParam;
use Psalm\Issue\MissingDocblockType;
use Psalm\IssueBuffer;
use Psalm\Scanner\FileScanner;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;

class DependencyFinderVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor, FileSource
{
    /** @var Aliases */
    private $aliases;

    /** @var Aliases */
    private $file_aliases;

    /**
     * @var string[]
     */
    private $fq_classlike_names = [];

    /** @var FileScanner */
    private $file_scanner;

    /** @var Codebase */
    private $codebase;

    /** @var string */
    private $file_path;

    /** @var bool */
    private $scan_deep;

    /** @var Config */
    private $config;

    /** @var bool */
    private $queue_strings_as_possible_type = false;

    /** @var array<string, string> */
    private $class_template_types = [];

    /** @var array<string, string> */
    private $function_template_types = [];

    /** @var FunctionLikeStorage[] */
    private $functionlike_storages = [];

    /** @var FileStorage */
    private $file_storage;

    /** @var ClassLikeStorage[] */
    private $classlike_storages = [];

    /** @var string[] */
    private $after_classlike_check_plugins;

    public function __construct(
        Codebase $codebase,
        FileStorage $file_storage,
        FileScanner $file_scanner
    ) {
        $this->codebase = $codebase;
        $this->file_scanner = $file_scanner;
        $this->file_path = $file_scanner->file_path;
        $this->scan_deep = $file_scanner->will_analyze;
        $this->config = $codebase->config;
        $this->aliases = $this->file_aliases = new Aliases();
        $this->file_storage = $file_storage;
        $this->after_classlike_check_plugins = $this->config->after_visit_classlikes;
    }

    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->file_aliases = $this->aliases;
            $this->aliases = new Aliases(
                $node->name ? implode('\\', $node->name->parts) : '',
                $this->aliases->uses,
                $this->aliases->functions,
                $this->aliases->constants
            );
        } elseif ($node instanceof PhpParser\Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $use_path = implode('\\', $use->name->parts);

                $use_alias = $use->alias ? $use->alias->name : $use->name->getLast();

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliases->functions[strtolower($use_alias)] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliases->constants[$use_alias] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliases->uses[strtolower($use_alias)] = $use_path;
                        break;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\GroupUse) {
            $use_prefix = implode('\\', $node->prefix->parts);

            foreach ($node->uses as $use) {
                $use_path = $use_prefix . '\\' . implode('\\', $use->name->parts);
                $use_alias = $use->alias ? $use->alias->name : $use->name->getLast();

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliases->functions[strtolower($use_alias)] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliases->constants[$use_alias] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliases->uses[strtolower($use_alias)] = $use_path;
                        break;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if ($node->name === null) {
                if (!$node instanceof PhpParser\Node\Stmt\Class_) {
                    throw new \LogicException('Anonymous classes are always classes');
                }

                $fq_classlike_name = ClassChecker::getAnonymousClassName($node, $this->file_path);
            } else {
                $fq_classlike_name =
                    ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $node->name->name;

                if ($this->codebase->classlike_storage_provider->has($fq_classlike_name)
                    && !$this->codebase->register_global_functions
                ) {
                    $other_file_path = null;

                    try {
                        $other_file_path = $this->codebase->scanner->getClassLikeFilePath($fq_classlike_name);
                    } catch (\UnexpectedValueException $e) {
                        // do nothing
                    }

                    if (IssueBuffer::accepts(
                        new DuplicateClass(
                            'Class ' . $fq_classlike_name . ' has already been defined'
                                . ($other_file_path ? ' in ' . $other_file_path : ''),
                            new CodeLocation($this->file_scanner, $node, null, true)
                        )
                    )) {
                        // fall through
                    }

                    return PhpParser\NodeTraverser::STOP_TRAVERSAL;
                }
            }

            $fq_classlike_name_lc = strtolower($fq_classlike_name);

            $this->file_storage->classlikes_in_file[$fq_classlike_name_lc] = $fq_classlike_name;

            $this->fq_classlike_names[] = $fq_classlike_name;

            $storage = $this->codebase->createClassLikeStorage($fq_classlike_name);

            $storage->location = new CodeLocation($this->file_scanner, $node, null, true);
            $storage->user_defined = !$this->codebase->register_global_functions;
            $storage->stubbed = $this->codebase->register_global_functions;

            $doc_comment = $node->getDocComment();

            $this->classlike_storages[] = $storage;

            if ($doc_comment) {
                $docblock_info = null;
                try {
                    $docblock_info = CommentChecker::extractClassLikeDocblockInfo(
                        (string)$doc_comment,
                        $doc_comment->getLine()
                    );
                } catch (DocblockParseException $e) {
                    IssueBuffer::accepts(
                        new InvalidDocblock(
                            $e->getMessage() . ' in docblock for ' . implode('.', $this->fq_classlike_names),
                            new CodeLocation($this->file_scanner, $node, null, true)
                        )
                    );
                }

                if ($docblock_info) {
                    if ($docblock_info->template_types) {
                        $storage->template_types = [];

                        foreach ($docblock_info->template_types as $template_type) {
                            if (count($template_type) === 3) {
                                $as_type_string = Type::getFQCLNFromString(
                                    $template_type[2],
                                    $this->aliases
                                );
                                $storage->template_types[$template_type[0]] = $as_type_string;
                            } else {
                                $storage->template_types[$template_type[0]] = 'mixed';
                            }
                        }

                        $this->class_template_types = $storage->template_types;

                        if ($docblock_info->template_parents) {
                            $storage->template_parents = [];

                            foreach ($docblock_info->template_parents as $template_parent) {
                                $storage->template_parents[$template_parent] = $template_parent;
                            }
                        }
                    }

                    if ($docblock_info->properties) {
                        foreach ($docblock_info->properties as $property) {
                            $pseudo_property_type_tokens = Type::fixUpLocalType(
                                $property['type'],
                                $this->aliases
                            );

                            $pseudo_property_type = Type::parseTokens($pseudo_property_type_tokens);
                            $pseudo_property_type->setFromDocblock();

                            if ($property['tag'] !== 'property-read') {
                                $storage->pseudo_property_set_types[$property['name']] = $pseudo_property_type;
                            }

                            if ($property['tag'] !== 'property-write') {
                                $storage->pseudo_property_get_types[$property['name']] = $pseudo_property_type;
                            }
                        }
                    }

                    $storage->deprecated = $docblock_info->deprecated;

                    $storage->sealed_properties = $docblock_info->sealed_properties;
                    $storage->sealed_methods = $docblock_info->sealed_methods;

                    $storage->suppressed_issues = $docblock_info->suppressed_issues;

                    foreach ($docblock_info->methods as $method) {
                        $storage->pseudo_methods[strtolower($method->name->name)]
                            = $this->registerFunctionLike($method, true);
                    }
                }
            }

            if ($node instanceof PhpParser\Node\Stmt\Class_) {
                $storage->abstract = (bool)$node->isAbstract();
                $storage->final = (bool)$node->isFinal();

                $this->codebase->classlikes->addFullyQualifiedClassName($fq_classlike_name, $this->file_path);

                if ($node->extends) {
                    $parent_fqcln = ClassLikeChecker::getFQCLNFromNameObject($node->extends, $this->aliases);
                    $this->codebase->scanner->queueClassLikeForScanning(
                        $parent_fqcln,
                        $this->file_path,
                        $this->scan_deep
                    );
                    $parent_fqcln_lc = strtolower($parent_fqcln);
                    $storage->parent_classes[$parent_fqcln_lc] = $parent_fqcln_lc;
                    $this->file_storage->required_classes[strtolower($parent_fqcln)] = $parent_fqcln;
                }

                foreach ($node->implements as $interface) {
                    $interface_fqcln = ClassLikeChecker::getFQCLNFromNameObject($interface, $this->aliases);
                    $this->codebase->scanner->queueClassLikeForScanning($interface_fqcln, $this->file_path);
                    $storage->class_implements[strtolower($interface_fqcln)] = $interface_fqcln;
                    $this->file_storage->required_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Interface_) {
                $storage->is_interface = true;
                $this->codebase->classlikes->addFullyQualifiedInterfaceName($fq_classlike_name, $this->file_path);

                foreach ($node->extends as $interface) {
                    $interface_fqcln = ClassLikeChecker::getFQCLNFromNameObject($interface, $this->aliases);
                    $this->codebase->scanner->queueClassLikeForScanning($interface_fqcln, $this->file_path);
                    $storage->parent_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
                    $this->file_storage->required_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Trait_) {
                $storage->is_trait = true;
                $this->file_storage->has_trait = true;
                $this->codebase->classlikes->addFullyQualifiedTraitName($fq_classlike_name, $this->file_path);
                $this->codebase->classlikes->addTraitNode(
                    $fq_classlike_name,
                    $node,
                    $this->aliases
                );
            }

            foreach ($node->stmts as $node_stmt) {
                if ($node_stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                    $this->visitClassConstDeclaration($node_stmt, $storage, $fq_classlike_name);
                }
            }

            foreach ($node->stmts as $node_stmt) {
                if ($node_stmt instanceof PhpParser\Node\Stmt\Property) {
                    $this->visitPropertyDeclaration($node_stmt, $this->config, $storage, $fq_classlike_name);
                }
            }
        } elseif (($node instanceof PhpParser\Node\Expr\New_
                || $node instanceof PhpParser\Node\Expr\Instanceof_
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                || $node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\StaticCall)
            && $node->class instanceof PhpParser\Node\Name
        ) {
            $fq_classlike_name = ClassLikeChecker::getFQCLNFromNameObject($node->class, $this->aliases);

            if (!in_array(strtolower($fq_classlike_name), ['self', 'static', 'parent'], true)) {
                $this->codebase->scanner->queueClassLikeForScanning(
                    $fq_classlike_name,
                    $this->file_path,
                    false,
                    !($node instanceof PhpParser\Node\Expr\ClassConstFetch)
                        || !($node->name instanceof PhpParser\Node\Identifier)
                        || strtolower($node->name->name) !== 'class'
                );
                $this->file_storage->referenced_classlikes[strtolower($fq_classlike_name)] = $fq_classlike_name;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TryCatch) {
            foreach ($node->catches as $catch) {
                foreach ($catch->types as $catch_type) {
                    $catch_fqcln = ClassLikeChecker::getFQCLNFromNameObject($catch_type, $this->aliases);

                    if (!in_array(strtolower($catch_fqcln), ['self', 'static', 'parent'], true)) {
                        $this->codebase->scanner->queueClassLikeForScanning($catch_fqcln, $this->file_path);
                        $this->file_storage->referenced_classlikes[strtolower($catch_fqcln)] = $catch_fqcln;
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            $this->registerFunctionLike($node);

            if (!$this->scan_deep) {
                return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($node instanceof PhpParser\Node\Expr\FuncCall && $node->name instanceof PhpParser\Node\Name) {
            $function_id = implode('\\', $node->name->parts);
            if (CallMap::inCallMap($function_id)) {
                $function_params = CallMap::getParamsFromCallMap($function_id);

                if ($function_params) {
                    foreach ($function_params as $function_param_group) {
                        foreach ($function_param_group as $function_param) {
                            if ($function_param->type) {
                                $function_param->type->queueClassLikesForScanning(
                                    $this->codebase,
                                    $this->file_storage
                                );
                            }
                        }
                    }
                }

                $return_type = CallMap::getReturnTypeFromCallMap($function_id);

                $return_type->queueClassLikesForScanning($this->codebase, $this->file_storage);

                if ($function_id === 'get_class') {
                    $this->queue_strings_as_possible_type = true;
                }

                if ($function_id === 'define') {
                    $first_arg_value = isset($node->args[0]) ? $node->args[0]->value : null;
                    $second_arg_value = isset($node->args[1]) ? $node->args[1]->value : null;
                    if ($first_arg_value instanceof PhpParser\Node\Scalar\String_ && $second_arg_value) {
                        $const_type = StatementsChecker::getSimpleType($second_arg_value, $this->file_scanner)
                            ?: Type::getMixed();
                        $const_name = $first_arg_value->value;

                        if ($this->functionlike_storages) {
                            $functionlike_storage =
                                $this->functionlike_storages[count($this->functionlike_storages) - 1];
                            $functionlike_storage->defined_constants[$const_name] = $const_type;
                        } else {
                            $this->file_storage->constants[$const_name] = $const_type;
                            $this->file_storage->declaring_constants[$const_name] = $this->file_path;
                        }
                    }
                }

                $mapping_function_ids = [];

                if (($function_id === 'array_map' && isset($node->args[0]))
                    || ($function_id === 'array_filter' && isset($node->args[1]))
                ) {
                    $node_arg_value = $function_id = 'array_map' ? $node->args[0]->value : $node->args[1]->value;

                    if ($node_arg_value instanceof PhpParser\Node\Scalar\String_
                        || $node_arg_value instanceof PhpParser\Node\Expr\Array_
                    ) {
                        $mapping_function_ids = CallChecker::getFunctionIdsFromCallableArg(
                            $this->file_scanner,
                            $node_arg_value
                        );
                    }

                    foreach ($mapping_function_ids as $potential_method_id) {
                        if (strpos($potential_method_id, '::') === false) {
                            continue;
                        }

                        list($callable_fqcln) = explode('::', $potential_method_id);

                        if (!in_array(strtolower($callable_fqcln), ['self', 'parent', 'static'], true)) {
                            $this->codebase->scanner->queueClassLikeForScanning(
                                $callable_fqcln,
                                $this->file_path
                            );
                        }
                    }
                }

                if ($function_id === 'func_get_arg'
                    || $function_id === 'func_get_args'
                    || $function_id === 'func_num_args'
                ) {
                    $function_like_storage = end($this->functionlike_storages);

                    if ($function_like_storage) {
                        $function_like_storage->variadic = true;
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TraitUse) {
            if (!$this->classlike_storages) {
                throw new \LogicException('$this->classlike_storages should not be empty');
            }

            $storage = $this->classlike_storages[count($this->classlike_storages) - 1];

            $method_map = $storage->trait_alias_map ?: [];

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                    if ($adaptation->newName) {
                        $method_map[strtolower($adaptation->method->name)] = strtolower($adaptation->newName->name);
                    }
                }
            }

            $storage->trait_alias_map = $method_map;

            foreach ($node->traits as $trait) {
                $trait_fqcln = ClassLikeChecker::getFQCLNFromNameObject($trait, $this->aliases);
                $this->codebase->scanner->queueClassLikeForScanning($trait_fqcln, $this->file_path, $this->scan_deep);
                $storage->used_traits[strtolower($trait_fqcln)] = $trait_fqcln;
                $this->file_storage->required_classes[strtolower($trait_fqcln)] = $trait_fqcln;
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Include_) {
            $this->visitInclude($node);
        } elseif ($node instanceof PhpParser\Node\Scalar\String_ && $this->queue_strings_as_possible_type) {
            if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $node->value)) {
                $this->codebase->scanner->queueClassLikeForScanning($node->value, $this->file_path, false, false);
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Assign
            || $node instanceof PhpParser\Node\Expr\AssignOp
            || $node instanceof PhpParser\Node\Expr\AssignRef
        ) {
            if ($doc_comment = $node->getDocComment()) {
                $var_comments = [];

                try {
                    $var_comments = CommentChecker::getTypeFromComment(
                        (string)$doc_comment,
                        $this->file_scanner,
                        $this->aliases,
                        null,
                        null
                    );
                } catch (DocblockParseException $e) {
                    // do nothing
                }

                foreach ($var_comments as $var_comment) {
                    $var_type = $var_comment->type;
                    $var_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $const_type = StatementsChecker::getSimpleType($const->value, $this->file_scanner)
                    ?: Type::getMixed();

                if ($this->codebase->register_global_functions) {
                    $this->codebase->addStubbedConstantType($const->name->name, $const_type);
                } else {
                    $this->file_storage->constants[$const->name->name] = $const_type;
                    $this->file_storage->declaring_constants[$const->name->name] = $this->file_path;
                }
            }
        } elseif ($this->codebase->register_global_functions && $node instanceof PhpParser\Node\Stmt\If_) {
            if ($node->cond instanceof PhpParser\Node\Expr\BooleanNot) {
                if ($node->cond->expr instanceof PhpParser\Node\Expr\FuncCall
                    && $node->cond->expr->name instanceof PhpParser\Node\Name
                ) {
                    if ($node->cond->expr->name->parts === ['function_exists']
                        && isset($node->cond->expr->args[0])
                        && $node->cond->expr->args[0]->value instanceof PhpParser\Node\Scalar\String_
                        && function_exists($node->cond->expr->args[0]->value->value)
                    ) {
                        return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
                    }

                    if ($node->cond->expr->name->parts === ['class_exists']
                        && isset($node->cond->expr->args[0])
                        && $node->cond->expr->args[0]->value instanceof PhpParser\Node\Scalar\String_
                        && class_exists($node->cond->expr->args[0]->value->value, false)
                    ) {
                        return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
                    }
                }
            }
        }
    }

    /**
     * @return null
     */
    public function leaveNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->aliases = $this->file_aliases;
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if (!$this->fq_classlike_names) {
                throw new \LogicException('$this->fq_classlike_names should not be empty');
            }

            $fq_classlike_name = array_pop($this->fq_classlike_names);

            if (PropertyMap::inPropertyMap($fq_classlike_name)) {
                $public_mapped_properties = PropertyMap::getPropertyMap()[strtolower($fq_classlike_name)];

                if (!$this->classlike_storages) {
                    throw new \UnexpectedValueException('$this->classlike_storages cannot be empty');
                }

                $storage = $this->classlike_storages[count($this->classlike_storages) - 1];

                foreach ($public_mapped_properties as $property_name => $public_mapped_property) {
                    $property_type = Type::parseString($public_mapped_property);

                    $property_type->queueClassLikesForScanning($this->codebase, $this->file_storage);

                    if (!isset($storage->properties[$property_name])) {
                        $storage->properties[$property_name] = new PropertyStorage();
                    }

                    $storage->properties[$property_name]->type = $property_type;
                    $storage->properties[$property_name]->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;

                    $property_id = $fq_classlike_name . '::$' . $property_name;

                    $storage->declaring_property_ids[$property_name] = $property_id;
                    $storage->appearing_property_ids[$property_name] = $property_id;
                }
            }

            $classlike_storage = array_pop($this->classlike_storages);

            $this->class_template_types = [];

            if ($this->after_classlike_check_plugins) {
                $file_manipulations = [];

                foreach ($this->after_classlike_check_plugins as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterVisitClassLike(
                        $node,
                        $classlike_storage,
                        $this->file_scanner,
                        $this->aliases,
                        $file_manipulations
                    );
                }
            }

            $this->codebase->cacheClassLikeStorage($classlike_storage, $this->file_path);
        } elseif ($node instanceof PhpParser\Node\Stmt\Function_
            || $node instanceof PhpParser\Node\Stmt\ClassMethod
        ) {
            $this->queue_strings_as_possible_type = false;

            $this->function_template_types = [];
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            array_pop($this->functionlike_storages);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\FunctionLike $stmt
     * @param  bool $fake_method in the case of @method annotations we do something a little strange
     *
     * @return FunctionLikeStorage
     */
    private function registerFunctionLike(PhpParser\Node\FunctionLike $stmt, $fake_method = false)
    {
        $class_storage = null;

        if ($fake_method && $stmt instanceof PhpParser\Node\Stmt\Function_) {
            $cased_function_id = '@method ' . $stmt->name->name;

            $storage = new FunctionLikeStorage();
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $cased_function_id =
                ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $stmt->name->name;
            $function_id = strtolower($cased_function_id);

            if ($this->codebase->register_global_functions) {
                $storage = new FunctionLikeStorage();
                $this->codebase->functions->addStubbedFunction($function_id, $storage);
            } else {
                if (isset($this->file_storage->functions[$function_id])) {
                    return $this->file_storage->functions[$function_id];
                }

                $storage = $this->file_storage->functions[$function_id] = new FunctionLikeStorage();
                $this->file_storage->declaring_function_ids[$function_id] = strtolower($this->file_path);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            if (!$this->fq_classlike_names) {
                throw new \LogicException('$this->fq_classlike_names should not be null');
            }

            $fq_classlike_name = $this->fq_classlike_names[count($this->fq_classlike_names) - 1];

            $function_id = $fq_classlike_name . '::' . strtolower($stmt->name->name);
            $cased_function_id = $fq_classlike_name . '::' . $stmt->name->name;

            if (!$this->classlike_storages) {
                throw new \UnexpectedValueException('$class_storages cannot be empty for ' . $function_id);
            }

            $class_storage = $this->classlike_storages[count($this->classlike_storages) - 1];

            if (isset($class_storage->methods[strtolower($stmt->name->name)])) {
                throw new \InvalidArgumentException('Cannot re-register ' . $function_id);
            }

            $storage = $class_storage->methods[strtolower($stmt->name->name)] = new MethodStorage();

            $class_name_parts = explode('\\', $fq_classlike_name);
            $class_name = array_pop($class_name_parts);

            if (strtolower($stmt->name->name) === strtolower($class_name) &&
                !isset($class_storage->methods['__construct']) &&
                strpos($fq_classlike_name, '\\') === false
            ) {
                $this->codebase->methods->setDeclaringMethodId(
                    $fq_classlike_name . '::__construct',
                    $function_id
                );
                $this->codebase->methods->setAppearingMethodId(
                    $fq_classlike_name . '::__construct',
                    $function_id
                );
            }

            $class_storage->declaring_method_ids[strtolower($stmt->name->name)] = $function_id;
            $class_storage->appearing_method_ids[strtolower($stmt->name->name)] = $function_id;

            if (!$stmt->isPrivate() || $stmt->name->name === '__construct' || $class_storage->is_trait) {
                $class_storage->inheritable_method_ids[strtolower($stmt->name->name)] = $function_id;
            }

            if (!isset($class_storage->overridden_method_ids[strtolower($stmt->name->name)])) {
                $class_storage->overridden_method_ids[strtolower($stmt->name->name)] = [];
            }

            $storage->is_static = (bool) $stmt->isStatic();
            $storage->abstract = (bool) $stmt->isAbstract();

            $storage->final = $class_storage->final || $stmt->isFinal();

            if ($stmt->isPrivate()) {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PRIVATE;
            } elseif ($stmt->isProtected()) {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PROTECTED;
            } else {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;
            }
        } else {
            $function_id = $cased_function_id = $this->file_path
                . ':' . $stmt->getLine()
                . ':' . (int) $stmt->getAttribute('startFilePos') . ':-:closure';

            $storage = $this->file_storage->functions[$function_id] = new FunctionLikeStorage();
        }

        $this->functionlike_storages[] = $storage;

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod || $stmt instanceof PhpParser\Node\Stmt\Function_) {
            $storage->cased_name = $stmt->name->name;
        }

        $storage->location = new CodeLocation($this->file_scanner, $stmt, null, true);

        $required_param_count = 0;
        $i = 0;
        $has_optional_param = false;

        $existing_params = [];

        /** @var PhpParser\Node\Param $param */
        foreach ($stmt->getParams() as $param) {
            $param_array = $this->getTranslatedFunctionParam($param);

            if (isset($existing_params['$' . $param_array->name])) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Duplicate param $' . $param_array->name . ' in docblock for ' . $cased_function_id,
                        new CodeLocation($this->file_scanner, $param, null, true)
                    )
                )) {
                    continue;
                }
            }

            $existing_params['$' . $param_array->name] = $i;
            $storage->param_types[$param_array->name] = $param_array->type;
            $storage->params[] = $param_array;

            if (!$param_array->is_optional) {
                $required_param_count = $i + 1;

                if (!$param->variadic && $has_optional_param && is_string($param->var->name)) {
                    if (IssueBuffer::accepts(
                        new MisplacedRequiredParam(
                            'Required param $' . $param->var->name . ' should come before any optional params in ' .
                            $cased_function_id,
                            new CodeLocation($this->file_scanner, $param, null, true)
                        )
                    )) {
                        // fall through
                    }
                }
            } else {
                $has_optional_param = true;
            }

            ++$i;
        }

        $storage->required_param_count = $required_param_count;

        if (($stmt instanceof PhpParser\Node\Stmt\Function_
                || $stmt instanceof PhpParser\Node\Stmt\ClassMethod)
            && strpos($stmt->name->name, 'assert') === 0
            && $stmt->stmts
        ) {
            $var_assertions = [];

            foreach ($stmt->stmts as $function_stmt) {
                if ($function_stmt instanceof PhpParser\Node\Stmt\If_) {
                    $final_actions = \Psalm\Checker\ScopeChecker::getFinalControlActions(
                        $function_stmt->stmts,
                        false,
                        false
                    );

                    if ($final_actions !== [\Psalm\Checker\ScopeChecker::ACTION_END]) {
                        continue;
                    }

                    $if_clauses = \Psalm\Type\Algebra::getFormula(
                        $function_stmt->cond,
                        $this->fq_classlike_names
                            ? $this->fq_classlike_names[count($this->fq_classlike_names) - 1]
                            : null,
                        $this->file_scanner
                    );

                    $negated_formula = \Psalm\Type\Algebra::negateFormula($if_clauses);

                    $rules = \Psalm\Type\Algebra::getTruthsFromFormula($negated_formula);

                    foreach ($rules as $var_id => $rule) {
                        foreach ($rule as $rule_part) {
                            if (count($rule_part) > 1) {
                                continue 2;
                            }
                        }

                        if (isset($existing_params[$var_id])) {
                            $param_offset = $existing_params[$var_id];

                            $var_assertions[] = new \Psalm\Storage\Assertion(
                                $param_offset,
                                $rule
                            );
                        } elseif (strpos($var_id, '$this->') === 0) {
                            $var_assertions[] = new \Psalm\Storage\Assertion(
                                $var_id,
                                $rule
                            );
                        }
                    }
                }
            }

            $storage->assertions = $var_assertions;
        }

        if (!$this->scan_deep
            && ($stmt instanceof PhpParser\Node\Stmt\Function_
                || $stmt instanceof PhpParser\Node\Stmt\ClassMethod
                || $stmt instanceof PhpParser\Node\Expr\Closure)
            && $stmt->stmts
        ) {
            // pick up func_get_args that would otherwise be missed
            foreach ($stmt->stmts as $function_stmt) {
                if ($function_stmt instanceof PhpParser\Node\Stmt\Expression
                    && $function_stmt->expr instanceof PhpParser\Node\Expr\Assign
                    && ($function_stmt->expr->expr instanceof PhpParser\Node\Expr\FuncCall)
                    && ($function_stmt->expr->expr->name instanceof PhpParser\Node\Name)
                ) {
                    $function_id = implode('\\', $function_stmt->expr->expr->name->parts);

                    if ($function_id === 'func_get_arg'
                        || $function_id === 'func_get_args'
                        || $function_id === 'func_num_args'
                    ) {
                        $storage->variadic = true;
                    }
                }
            }
        }

        $parser_return_type = $stmt->getReturnType();

        if ($parser_return_type) {
            $suffix = '';

            if ($parser_return_type instanceof PhpParser\Node\NullableType) {
                $suffix = '|null';
                $parser_return_type = $parser_return_type->type;
            }

            if ($parser_return_type instanceof PhpParser\Node\Identifier) {
                $return_type_string = $parser_return_type->name . $suffix;
            } else {
                $return_type_fq_classlike_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $parser_return_type,
                    $this->aliases
                );

                if (!in_array(strtolower($return_type_fq_classlike_name), ['self', 'parent'], true)) {
                    $this->codebase->scanner->queueClassLikeForScanning(
                        $return_type_fq_classlike_name,
                        $this->file_path
                    );
                }

                $return_type_string = $return_type_fq_classlike_name . $suffix;
            }

            $storage->return_type = Type::parseString($return_type_string, true);
            $storage->return_type_location = new CodeLocation(
                $this->file_scanner,
                $stmt,
                null,
                false,
                CodeLocation::FUNCTION_RETURN_TYPE
            );

            if ($stmt->returnsByRef()) {
                $storage->return_type->by_ref = true;
            }

            $storage->signature_return_type = $storage->return_type;
            $storage->signature_return_type_location = $storage->return_type_location;
        }

        if ($stmt->returnsByRef()) {
            $storage->returns_by_ref = true;
        }

        $doc_comment = $stmt->getDocComment();

        if (!$doc_comment) {
            return $storage;
        }

        try {
            $docblock_info = CommentChecker::extractFunctionDocblockInfo(
                (string)$doc_comment,
                $doc_comment->getLine()
            );
        } catch (IncorrectDocblockException $e) {
            if (IssueBuffer::accepts(
                new MissingDocblockType(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                )
            )) {
                // fall through
            }

            $docblock_info = null;
        } catch (DocblockParseException $e) {
            if (IssueBuffer::accepts(
                new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                )
            )) {
                // fall through
            }

            $docblock_info = null;
        }

        if (!$docblock_info) {
            return $storage;
        }

        if ($docblock_info->deprecated) {
            $storage->deprecated = true;
        }

        if ($docblock_info->variadic) {
            $storage->variadic = true;
        }

        if ($docblock_info->ignore_nullable_return && $storage->return_type) {
            $storage->return_type->ignore_nullable_issues = true;
        }

        if ($docblock_info->ignore_falsable_return && $storage->return_type) {
            $storage->return_type->ignore_falsable_issues = true;
        }

        $storage->suppressed_issues = $docblock_info->suppress;

        if (!$this->config->use_docblock_types) {
            return $storage;
        }

        $template_types = $class_storage && $class_storage->template_types ? $class_storage->template_types : null;

        if ($docblock_info->template_types) {
            $storage->template_types = [];

            foreach ($docblock_info->template_types as $template_type) {
                if (count($template_type) === 3) {
                    $as_type_string = Type::getFQCLNFromString($template_type[2], $this->aliases);
                    $storage->template_types[$template_type[0]] = $as_type_string;
                } else {
                    $storage->template_types[$template_type[0]] = 'mixed';
                }
            }

            $template_types = array_merge($template_types ?: [], $storage->template_types);

            $this->function_template_types = $template_types;
        }

        if ($docblock_info->template_typeofs) {
            $storage->template_typeof_params = [];

            foreach ($docblock_info->template_typeofs as $template_typeof) {
                foreach ($storage->params as $i => $param) {
                    if ($param->name === $template_typeof['param_name']) {
                        $storage->template_typeof_params[$i] = $template_typeof['template_type'];
                        break;
                    }
                }
            }
        }

        if ($docblock_info->assertions) {
            $storage->assertions = [];

            foreach ($docblock_info->assertions as $assertion) {
                foreach ($storage->params as $i => $param) {
                    if ($param->name === $assertion['param_name']) {
                        $storage->assertions[] = new \Psalm\Storage\Assertion(
                            $i,
                            [[$assertion['type']]]
                        );
                        continue 2;
                    }
                }

                $storage->assertions[] = new \Psalm\Storage\Assertion(
                    $assertion['param_name'],
                    [[$assertion['type']]]
                );
            }
        }

        if ($docblock_info->if_true_assertions) {
            $storage->assertions = [];

            foreach ($docblock_info->if_true_assertions as $assertion) {
                foreach ($storage->params as $i => $param) {
                    if ($param->name === $assertion['param_name']) {
                        $storage->if_true_assertions[] = new \Psalm\Storage\Assertion(
                            $i,
                            [[$assertion['type']]]
                        );
                        continue 2;
                    }
                }

                $storage->if_true_assertions[] = new \Psalm\Storage\Assertion(
                    $assertion['param_name'],
                    [[$assertion['type']]]
                );
            }
        }

        if ($docblock_info->if_false_assertions) {
            $storage->assertions = [];

            foreach ($docblock_info->if_false_assertions as $assertion) {
                foreach ($storage->params as $i => $param) {
                    if ($param->name === $assertion['param_name']) {
                        $storage->if_false_assertions[] = new \Psalm\Storage\Assertion(
                            $i,
                            [[$assertion['type']]]
                        );
                        continue 2;
                    }
                }

                $storage->if_false_assertions[] = new \Psalm\Storage\Assertion(
                    $assertion['param_name'],
                    [[$assertion['type']]]
                );
            }
        }

        if ($docblock_info->return_type) {
            if (!$storage->return_type || $docblock_info->return_type !== $storage->return_type->getId()) {
                $storage->has_template_return_type =
                    $template_types !== null &&
                    count(
                        array_intersect(
                            Type::tokenize($docblock_info->return_type),
                            array_keys($template_types)
                        )
                    ) > 0;

                $docblock_return_type = $docblock_info->return_type;

                if (!$storage->return_type_location) {
                    $storage->return_type_location = new CodeLocation(
                        $this->file_scanner,
                        $stmt,
                        null,
                        false,
                        CodeLocation::FUNCTION_PHPDOC_RETURN_TYPE,
                        $docblock_info->return_type
                    );
                }

                if ($docblock_return_type) {
                    try {
                        $fixed_type_tokens = Type::fixUpLocalType(
                            $docblock_return_type,
                            $this->aliases,
                            $this->function_template_types + $this->class_template_types
                        );

                        $storage->return_type = Type::parseTokens(
                            $fixed_type_tokens,
                            false,
                            $this->function_template_types + $this->class_template_types
                        );
                        $storage->return_type->setFromDocblock();

                        if ($storage->signature_return_type) {
                            $all_typehint_types_match = true;
                            $signature_return_atomic_types = $storage->signature_return_type->getTypes();

                            foreach ($storage->return_type->getTypes() as $key => $type) {
                                if (isset($signature_return_atomic_types[$key])) {
                                    $type->from_docblock = false;
                                } else {
                                    $all_typehint_types_match = false;
                                }
                            }

                            if ($all_typehint_types_match) {
                                $storage->return_type->from_docblock = false;
                            }
                        }

                        $storage->return_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
                    } catch (TypeParseTreeException $e) {
                        if (IssueBuffer::accepts(
                            new InvalidDocblock(
                                $e->getMessage() . ' in docblock for ' . $cased_function_id,
                                new CodeLocation($this->file_scanner, $stmt, null, true)
                            )
                        )) {
                            // fall through
                        }
                    }
                }

                if ($storage->return_type && $docblock_info->ignore_nullable_return) {
                    $storage->return_type->ignore_nullable_issues = true;
                }

                if ($storage->return_type && $docblock_info->ignore_falsable_return) {
                    $storage->return_type->ignore_falsable_issues = true;
                }

                if ($stmt->returnsByRef() && $storage->return_type) {
                    $storage->return_type->by_ref = true;
                }

                if ($docblock_info->return_type_line_number) {
                    $storage->return_type_location->setCommentLine($docblock_info->return_type_line_number);
                }
            }
        }

        if ($docblock_info->params) {
            $this->improveParamsFromDocblock(
                $storage,
                $docblock_info->params,
                $stmt
            );
        }

        return $storage;
    }

    /**
     * @param  PhpParser\Node\Param $param
     *
     * @return FunctionLikeParameter
     */
    public function getTranslatedFunctionParam(PhpParser\Node\Param $param)
    {
        $param_type = null;

        $is_nullable = $param->default !== null &&
            $param->default instanceof PhpParser\Node\Expr\ConstFetch &&
            $param->default->name instanceof PhpParser\Node\Name &&
            strtolower($param->default->name->parts[0]) === 'null';

        $param_typehint = $param->type;

        if ($param_typehint instanceof PhpParser\Node\NullableType) {
            $is_nullable = true;
            $param_typehint = $param_typehint->type;
        }

        if ($param_typehint) {
            if ($param_typehint instanceof PhpParser\Node\Identifier) {
                $param_type_string = $param_typehint->name;
            } elseif ($param_typehint instanceof PhpParser\Node\Name\FullyQualified) {
                $param_type_string = (string)$param_typehint;
                $this->codebase->scanner->queueClassLikeForScanning($param_type_string, $this->file_path);
            } elseif (strtolower($param_typehint->parts[0]) === 'self') {
                $param_type_string = $this->fq_classlike_names[count($this->fq_classlike_names) - 1];
            } else {
                $param_type_string = ClassLikeChecker::getFQCLNFromNameObject($param_typehint, $this->aliases);

                if (!in_array(strtolower($param_type_string), ['self', 'static', 'parent'], true)) {
                    $this->codebase->scanner->queueClassLikeForScanning($param_type_string, $this->file_path);
                    $this->file_storage->referenced_classlikes[strtolower($param_type_string)] = $param_type_string;
                }
            }

            if ($param_type_string) {
                if ($is_nullable) {
                    $param_type_string .= '|null';
                }

                $param_type = Type::parseString($param_type_string, true);

                if ($param->variadic) {
                    $param_type = new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getInt(),
                            $param_type,
                        ]),
                    ]);
                }
            }
        } elseif ($param->variadic) {
            $param_type = new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    Type::getMixed(),
                ]),
            ]);
        }

        $is_optional = $param->default !== null;

        if (!is_string($param->var->name)) {
            throw new \UnexpectedValueException('Not expecting param name to be non-string');
        }

        return new FunctionLikeParameter(
            $param->var->name,
            $param->byRef,
            $param_type,
            new CodeLocation($this->file_scanner, $param, null, false, CodeLocation::FUNCTION_PARAM_VAR),
            $param_typehint
                ? new CodeLocation($this->file_scanner, $param, null, false, CodeLocation::FUNCTION_PARAM_TYPE)
                : null,
            $is_optional,
            $is_nullable,
            $param->variadic
        );
    }

    /**
     * @param  FunctionLikeStorage          $storage
     * @param  array<int, array{type:string,name:string,line_number:int}>  $docblock_params
     * @param  PhpParser\Node\FunctionLike  $function
     *
     * @return void
     */
    private function improveParamsFromDocblock(
        FunctionLikeStorage $storage,
        array $docblock_params,
        PhpParser\Node\FunctionLike $function
    ) {
        $base = $this->fq_classlike_names
            ? $this->fq_classlike_names[count($this->fq_classlike_names) - 1] . '::'
            : '';

        $cased_method_id = $base . $storage->cased_name;

        foreach ($docblock_params as $docblock_param) {
            $param_name = $docblock_param['name'];
            $docblock_param_variadic = false;

            if (substr($param_name, 0, 3) === '...') {
                $docblock_param_variadic = true;
                $param_name = substr($param_name, 3);
            }

            $param_name = substr($param_name, 1);

            $storage_param = null;

            foreach ($storage->params as $function_signature_param) {
                if ($function_signature_param->name === $param_name) {
                    $storage_param = $function_signature_param;
                    break;
                }
            }

            if ($storage_param === null) {
                continue;
            }

            $code_location = new CodeLocation(
                $this->file_scanner,
                $function,
                null,
                true,
                CodeLocation::FUNCTION_PHPDOC_PARAM_TYPE,
                $docblock_param['type']
            );

            $code_location->setCommentLine($docblock_param['line_number']);

            try {
                $new_param_type = Type::parseTokens(
                    Type::fixUpLocalType(
                        $docblock_param['type'],
                        $this->aliases,
                        $this->function_template_types + $this->class_template_types
                    ),
                    false,
                    $this->function_template_types + $this->class_template_types
                );
            } catch (TypeParseTreeException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        $e->getMessage() . ' in docblock for ' . $cased_method_id,
                        $code_location
                    )
                )) {
                    // fall through
                }

                continue;
            }

            $new_param_type->setFromDocblock();

            $new_param_type->queueClassLikesForScanning(
                $this->codebase,
                $this->file_storage,
                $storage->template_types ?: []
            );

            if ($docblock_param_variadic) {
                $new_param_type = new Type\Union([
                    new Type\Atomic\TArray([
                        Type::getInt(),
                        $new_param_type,
                    ]),
                ]);
            }

            $existing_param_type_nullable = $storage_param->is_nullable;

            if (!$storage_param->type || $storage_param->type->isMixed() || $storage->template_types) {
                if ($existing_param_type_nullable && !$new_param_type->isNullable()) {
                    $new_param_type->addType(new Type\Atomic\TNull());
                }

                $storage_param->type = $new_param_type;
                $storage_param->type_location = $code_location;
                continue;
            }

            $storage_param_atomic_types = $storage_param->type->getTypes();

            $all_types_match = true;
            $all_typehint_types_match = true;

            foreach ($new_param_type->getTypes() as $key => $type) {
                if (isset($storage_param_atomic_types[$key])) {
                    if ($storage_param_atomic_types[$key]->getId() !== $type->getId()) {
                        $all_types_match = false;
                    }

                    $type->from_docblock = false;
                } else {
                    $all_types_match = false;
                    $all_typehint_types_match = false;
                }
            }

            if ($all_types_match) {
                continue;
            }

            if ($all_typehint_types_match) {
                $new_param_type->from_docblock = false;
            }

            if ($existing_param_type_nullable && !$new_param_type->isNullable()) {
                $new_param_type->addType(new Type\Atomic\TNull());
            }

            $storage_param->type = $new_param_type;
            $storage_param->type_location = $code_location;
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\Property    $stmt
     * @param   Config                          $config
     * @param   string                          $fq_classlike_name
     *
     * @return  void
     */
    private function visitPropertyDeclaration(
        PhpParser\Node\Stmt\Property $stmt,
        Config $config,
        ClassLikeStorage $storage,
        $fq_classlike_name
    ) {
        if (!$this->fq_classlike_names) {
            throw new \LogicException('$this->fq_classlike_names should not be empty');
        }

        $comment = $stmt->getDocComment();
        $var_comment = null;

        $property_is_initialized = false;

        $existing_constants = $storage->protected_class_constants
            + $storage->private_class_constants
            + $storage->public_class_constants;

        if ($comment && $comment->getText() && ($config->use_docblock_types || $config->use_docblock_property_types)) {
            if (preg_match('/[ \t\*]+@psalm-suppress[ \t]+PropertyNotSetInConstructor/', (string)$comment)) {
                $property_is_initialized = true;
            }

            try {
                $property_type_line_number = $comment->getLine();
                $var_comments = CommentChecker::getTypeFromComment(
                    $comment->getText(),
                    $this->file_scanner,
                    $this->aliases,
                    $this->function_template_types + $this->class_template_types,
                    $property_type_line_number
                );

                $var_comment = array_pop($var_comments);
            } catch (IncorrectDocblockException $e) {
                if (IssueBuffer::accepts(
                    new MissingDocblockType(
                        $e->getMessage(),
                        new CodeLocation($this->file_scanner, $stmt, null, true)
                    )
                )) {
                    // fall through
                }
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        $e->getMessage(),
                        new CodeLocation($this->file_scanner, $stmt, null, true)
                    )
                )) {
                    // fall through
                }
            }
        }

        $property_group_type = $var_comment ? $var_comment->type : null;

        if ($property_group_type) {
            $property_group_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
            $property_group_type->setFromDocblock();
        }

        foreach ($stmt->props as $property) {
            $property_type_location = null;
            $default_type = null;

            if (!$property_group_type) {
                if ($property->default) {
                    $default_type = StatementsChecker::getSimpleType(
                        $property->default,
                        $this->file_scanner,
                        $existing_constants,
                        $fq_classlike_name
                    );
                }

                $property_type = false;
            } else {
                if ($var_comment && $var_comment->line_number) {
                    $property_type_location = new CodeLocation(
                        $this->file_scanner,
                        $stmt,
                        null,
                        false,
                        CodeLocation::VAR_TYPE,
                        $var_comment->original_type
                    );
                    $property_type_location->setCommentLine($var_comment->line_number);
                }

                $property_type = count($stmt->props) === 1 ? $property_group_type : clone $property_group_type;
            }

            $property_storage = $storage->properties[$property->name->name] = new PropertyStorage();
            $property_storage->is_static = (bool)$stmt->isStatic();
            $property_storage->type = $property_type;
            $property_storage->location = new CodeLocation($this->file_scanner, $property->name);
            $property_storage->type_location = $property_type_location;
            $property_storage->has_default = $property->default ? true : false;
            $property_storage->suggested_type = $property_group_type ? null : $default_type;
            $property_storage->deprecated = $var_comment ? $var_comment->deprecated : false;

            if ($stmt->isPublic()) {
                $property_storage->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;
            } elseif ($stmt->isProtected()) {
                $property_storage->visibility = ClassLikeChecker::VISIBILITY_PROTECTED;
            } elseif ($stmt->isPrivate()) {
                $property_storage->visibility = ClassLikeChecker::VISIBILITY_PRIVATE;
            }

            $fq_classlike_name = $this->fq_classlike_names[count($this->fq_classlike_names) - 1];

            $property_id = $fq_classlike_name . '::$' . $property->name->name;

            $storage->declaring_property_ids[$property->name->name] = $property_id;
            $storage->appearing_property_ids[$property->name->name] = $property_id;

            if ($property_is_initialized) {
                $storage->initialized_properties[$property->name->name] = true;
            }

            if (!$stmt->isPrivate()) {
                $storage->inheritable_property_ids[$property->name->name] = $property_id;
            }
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\ClassConst  $stmt
     * @param   string $fq_classlike_name
     *
     * @return  void
     */
    private function visitClassConstDeclaration(
        PhpParser\Node\Stmt\ClassConst $stmt,
        ClassLikeStorage $storage,
        $fq_classlike_name
    ) {
        $existing_constants = $storage->protected_class_constants
            + $storage->private_class_constants
            + $storage->public_class_constants;

        foreach ($stmt->consts as $const) {
            $const_type = StatementsChecker::getSimpleType(
                $const->value,
                $this->file_scanner,
                $existing_constants,
                $fq_classlike_name
            ) ?: Type::getMixed();

            $existing_constants[$const->name->name] = $const_type;

            if ($stmt->isProtected()) {
                $storage->protected_class_constants[$const->name->name] = $const_type;
            } elseif ($stmt->isPrivate()) {
                $storage->private_class_constants[$const->name->name] = $const_type;
            } else {
                $storage->public_class_constants[$const->name->name] = $const_type;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Include_ $stmt
     *
     * @return void
     */
    public function visitInclude(PhpParser\Node\Expr\Include_ $stmt)
    {
        $config = Config::getInstance();

        if (!$config->allow_includes) {
            throw new FileIncludeException(
                'File includes are not allowed per your Psalm config - check the allowFileIncludes flag.'
            );
        }

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = $stmt->expr->value;

            // attempts to resolve using get_include_path dirs
            $include_path = IncludeChecker::resolveIncludePath($path_to_file, dirname($this->file_path));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if ($path_to_file[0] !== DIRECTORY_SEPARATOR) {
                $path_to_file = getcwd() . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = IncludeChecker::getPathTo($stmt->expr, $this->file_path);
        }

        if ($path_to_file) {
            $reduce_pattern = '/\/[^\/]+\/\.\.\//';

            while (preg_match($reduce_pattern, $path_to_file)) {
                $path_to_file = preg_replace($reduce_pattern, DIRECTORY_SEPARATOR, $path_to_file);
            }

            if ($this->file_path === $path_to_file) {
                return;
            }

            if ($this->codebase->fileExists($path_to_file)) {
                if ($this->scan_deep) {
                    $this->codebase->scanner->addFileToDeepScan($path_to_file);
                } else {
                    $this->codebase->scanner->addFileToShallowScan($path_to_file);
                }

                $this->file_storage->included_file_paths[strtolower($path_to_file)] = $path_to_file;

                return;
            }
        }

        return;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_scanner->getFileName();
    }

    /**
     * @return string
     */
    public function getCheckedFilePath()
    {
        return $this->file_scanner->getCheckedFilePath();
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->file_scanner->getCheckedFileName();
    }

    /**
     * @return Aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }
}
