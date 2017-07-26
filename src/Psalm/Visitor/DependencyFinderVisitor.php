<?php
namespace Psalm\Visitor;

use PhpParser;
use Psalm\Aliases;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\FileIncludeException;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MisplacedRequiredParam;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\TraitSource;
use Psalm\Type;

class DependencyFinderVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /** @var Aliases */
    protected $aliases;

    /** @var Aliases */
    protected $file_aliases;

    /**
     * @var bool
     */
    protected $in_classlike = false;

    /**
     * @var ?string
     */
    protected $fq_classlike_name = null;

    /** @var ProjectChecker */
    protected $project_checker;

    /** @var FileChecker */
    protected $file_checker;

    /** @var string */
    protected $file_path;

    /** @var bool */
    protected $scan_deep;

    /** @var Config */
    protected $config;

    /** @var bool */
    protected $queue_strings_as_possible_type = false;

    /** @var array<string, string> */
    protected $class_template_types = [];

    /** @var array<string, string> */
    protected $function_template_types = [];

    /** @var ?FunctionLikeStorage */
    protected $functionlike_storage;

    /**
     * @param ProjectChecker $project_checker
     * @param string $file_path
     */
    public function __construct(ProjectChecker $project_checker, FileChecker $file_checker)
    {
        $this->project_checker = $project_checker;
        $this->file_checker = $file_checker;
        $this->file_path = $file_checker->getFilePath();
        $this->scan_deep = $file_checker->will_analyze;
        $this->config = Config::getInstance();
        $this->aliases = $this->file_aliases = new Aliases();
    }

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

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliases->functions[strtolower($use->alias)] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliases->constants[$use->alias] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliases->uses[strtolower($use->alias)] = $use_path;
                        break;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\GroupUse) {
            $use_prefix = implode('\\', $node->prefix->parts);

            foreach ($node->uses as $use) {
                $use_path = $use_prefix . '\\' . implode('\\', $use->name->parts);

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliases->functions[strtolower($use->alias)] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliases->constants[$use->alias] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliases->uses[strtolower($use->alias)] = $use_path;
                        break;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if ($node->name === null) {
                if (!$node instanceof PhpParser\Node\Stmt\Class_) {
                    throw new \LogicException('Anonymous classes are always classes');
                }

                $fq_classlike_name = ClassChecker::getAnonymousClassName($node, $this->file_path);
                $fq_classlike_name_lc = strtolower($fq_classlike_name);
            } else {
                $fq_classlike_name = ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $node->name;
                $fq_classlike_name_lc = strtolower($fq_classlike_name);
                FileChecker::$storage[strtolower($this->file_path)]->classes_in_file[] = $fq_classlike_name_lc;
            }

            $this->fq_classlike_name = $fq_classlike_name;

            ClassLikeChecker::$storage[$fq_classlike_name_lc] = $storage = new ClassLikeStorage();
            $storage->name = $fq_classlike_name;
            $storage->location = new CodeLocation($this->file_checker, $node, null, true);
            $storage->user_defined = true;

            $doc_comment = $node->getDocComment();

            if ($doc_comment) {
                $docblock_info = null;

                try {
                    $docblock_info = CommentChecker::extractClassLikeDocblockInfo(
                        (string)$doc_comment,
                        $doc_comment->getLine()
                    );
                } catch (DocblockParseException $e) {
                    if (IssueBuffer::accepts(
                        new InvalidDocblock(
                            $e->getMessage() . ' in docblock for ' . $this->fq_classlike_name,
                            new CodeLocation($this->file_checker, $node, null, true)
                        )
                    )) {
                        // fall through
                    }
                }

                if ($docblock_info) {
                    if ($docblock_info->template_types) {
                        $storage->template_types = [];

                        foreach ($docblock_info->template_types as $template_type) {
                            if (count($template_type) === 3) {
                                $as_type_string = ClassLikeChecker::getFQCLNFromString(
                                    $template_type[2],
                                    $this->aliases
                                );
                                $storage->template_types[$template_type[0]] = $as_type_string;
                            } else {
                                $storage->template_types[$template_type[0]] = 'mixed';
                            }
                        }

                        $this->class_template_types = $storage->template_types;
                    }

                    if ($docblock_info->properties) {
                        foreach ($docblock_info->properties as $property) {
                            $pseudo_property_type = Type::parseString($property['type']);

                            $storage->pseudo_property_set_types[$property['name']] = $pseudo_property_type;
                            $storage->pseudo_property_get_types[$property['name']] = $pseudo_property_type;
                        }
                    }

                    $storage->deprecated = $docblock_info->deprecated;
                }
            }

            if ($node instanceof PhpParser\Node\Stmt\Class_) {
                $storage->abstract = (bool)$node->isAbstract();

                $this->project_checker->addFullyQualifiedClassName($fq_classlike_name, $this->file_path);

                if ($node->extends) {
                    $parent_fqcln = ClassLikeChecker::getFQCLNFromNameObject($node->extends, $this->aliases);
                    $this->project_checker->queueClassLikeForScanning($parent_fqcln, $this->scan_deep);
                    $storage->parent_classes[] = strtolower($parent_fqcln);
                }

                foreach ($node->implements as $interface) {
                    $interface_fqcln = ClassLikeChecker::getFQCLNFromNameObject($interface, $this->aliases);
                    $this->project_checker->queueClassLikeForScanning($interface_fqcln);
                    $storage->class_implements[strtolower($interface_fqcln)] = $interface_fqcln;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Interface_) {
                $this->project_checker->addFullyQualifiedInterfaceName($fq_classlike_name, $this->file_path);

                foreach ($node->extends as $interface) {
                    $interface_fqcln = ClassLikeChecker::getFQCLNFromNameObject($interface, $this->aliases);
                    $this->project_checker->queueClassLikeForScanning($interface_fqcln);
                    $storage->parent_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Trait_) {
                $storage->is_trait = true;
                $this->project_checker->addFullyQualifiedTraitName($fq_classlike_name, $this->file_path);
                ClassLikeChecker::$trait_checkers[$fq_classlike_name_lc] = new TraitChecker(
                    $node,
                    new TraitSource($this->file_checker, $this->aliases),
                    $fq_classlike_name
                );
            }
        } elseif (($node instanceof PhpParser\Node\Expr\New_
                || $node instanceof PhpParser\Node\Expr\Instanceof_
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                || $node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\StaticCall)
            && $node->class instanceof PhpParser\Node\Name
        ) {
            $fq_classlike_name = ClassLikeChecker::getFQCLNFromNameObject($node->class, $this->aliases);

            if (!in_array($fq_classlike_name, ['self', 'static', 'parent'], true)) {
                $this->project_checker->queueClassLikeForScanning($fq_classlike_name);
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TryCatch) {
            foreach ($node->catches as $catch) {
                foreach ($catch->types as $catch_type) {
                    $catch_fqcln = ClassLikeChecker::getFQCLNFromNameObject($catch_type, $this->aliases);

                    if (!in_array($catch_fqcln, ['self', 'static', 'parent'], true)) {
                        $this->project_checker->queueClassLikeForScanning($catch_fqcln);
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
            if (FunctionChecker::inCallMap($function_id)) {
                $function_params = FunctionChecker::getParamsFromCallMap($function_id);

                if ($function_params) {
                    foreach ($function_params as $function_param_group) {
                        foreach ($function_param_group as $function_param) {
                            $function_param->type->queueClassLikesForScanning($this->project_checker);
                        }
                    }
                }

                $return_type = FunctionChecker::getReturnTypeFromCallMap($function_id);

                $return_type->queueClassLikesForScanning($this->project_checker);

                if ($function_id === 'get_class') {
                    $this->queue_strings_as_possible_type = true;
                }

                if ($function_id === 'define' && $this->functionlike_storage) {
                    $first_arg_value = isset($node->args[0]) ? $node->args[0]->value : null;
                    $second_arg_value = isset($node->args[1]) ? $node->args[1]->value : null;
                    if ($first_arg_value instanceof PhpParser\Node\Scalar\String_ && $second_arg_value) {
                        $this->functionlike_storage->defined_constants[$first_arg_value->value] =
                            StatementsChecker::getSimpleType($second_arg_value) ?: Type::getMixed();
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TraitUse) {
            if (!$this->fq_classlike_name) {
                throw new \LogicException('$this->fq_classlike_name should bot be null');
            }

            $storage = ClassLikeChecker::$storage[strtolower($this->fq_classlike_name)];

            $method_map = [];

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                    if ($adaptation->method && $adaptation->newName) {
                        $method_map[strtolower($adaptation->method)] = strtolower($adaptation->newName);
                    }
                }
            }

            $storage->trait_alias_map = $method_map;

            foreach ($node->traits as $trait) {
                $trait_fqcln = ClassLikeChecker::getFQCLNFromNameObject($trait, $this->aliases);
                $this->project_checker->queueClassLikeForScanning($trait_fqcln, $this->scan_deep);
                $storage->used_traits[strtolower($trait_fqcln)] = $trait_fqcln;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Property) {
            $this->visitPropertyDeclaration($node, $this->config);
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassConst) {
            $this->visitClassConstDeclaration($node);
        } elseif ($node instanceof PhpParser\Node\Expr\Include_) {
            $this->visitInclude($node);
        } elseif ($node instanceof PhpParser\Node\Scalar\String_ && $this->queue_strings_as_possible_type) {
            if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $node->value)) {
                $this->project_checker->queueClassLikeForScanning($node->value);
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Assign
            || $node instanceof PhpParser\Node\Expr\AssignOp
            || $node instanceof PhpParser\Node\Expr\AssignRef
        ) {
            if ($doc_comment = $node->getDocComment()) {
                $var_comment = CommentChecker::getTypeFromComment(
                    (string)$doc_comment,
                    null,
                    $this->file_checker,
                    $this->aliases,
                    null,
                    null
                );

                if ($var_comment) {
                    $var_type = Type::parseString($var_comment->type);

                    $var_type->queueClassLikesForScanning($this->project_checker);
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Const_) {
            if ($this->project_checker->register_global_functions) {
                foreach ($node->consts as $const) {
                    StatementsChecker::$stub_constants[$const->name] =
                        StatementsChecker::getSimpleType($const->value) ?: Type::getMixed();
                }
            }
        }
    }

    public function leaveNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->aliases = $this->file_aliases;
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if (!$this->fq_classlike_name) {
                throw new \LogicException('$this->fq_classlike_name should bot be null');
            }

            $fq_classlike_name = $this->fq_classlike_name;

            if (ClassLikeChecker::inPropertyMap($fq_classlike_name)) {
                $public_mapped_properties = ClassLikeChecker::getPropertyMap()[strtolower($fq_classlike_name)];

                $storage = ClassLikeChecker::$storage[strtolower($fq_classlike_name)];

                foreach ($public_mapped_properties as $property_name => $public_mapped_property) {
                    $property_type = Type::parseString($public_mapped_property);

                    $property_type->queueClassLikesForScanning($this->project_checker);

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

            $this->fq_classlike_name = null;
            $this->class_template_types = [];
        } elseif ($node instanceof PhpParser\Node\Stmt\Function_
            || $node instanceof PhpParser\Node\Stmt\ClassMethod
        ) {
            $this->queue_strings_as_possible_type = false;

            $this->function_template_types = [];
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            $this->functionlike_storage = null;
        }
    }

    /**
     * @param  PhpParser\Node\FunctionLike $stmt
     *
     * @return void
     */
    private function registerFunctionLike(PhpParser\Node\FunctionLike $stmt)
    {
        $class_storage = null;

        if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $cased_function_id = ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $stmt->name;
            $function_id = strtolower($cased_function_id);

            $project_checker = $this->project_checker;

            if ($project_checker->register_global_functions) {
                $storage = FunctionChecker::$stubbed_functions[$function_id] = new FunctionLikeStorage();
            } else {
                $file_storage = FileChecker::$storage[strtolower($this->file_path)];

                if (isset($file_storage->functions[$function_id])) {
                    return;
                }

                $storage = $file_storage->functions[$function_id] = new FunctionLikeStorage();
                $file_storage->declaring_function_ids[$function_id] = strtolower($this->file_path);
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            if (!$this->fq_classlike_name) {
                throw new \LogicException('$this->fq_classlike_name should bot be null');
            }

            $fq_classlike_name = $this->fq_classlike_name;

            $function_id = $fq_classlike_name . '::' . strtolower($stmt->name);
            $cased_function_id = $fq_classlike_name . '::' . $stmt->name;

            $fq_classlike_name_lc = strtolower($fq_classlike_name);

            if (!isset(ClassLikeChecker::$storage[$fq_classlike_name_lc])) {
                throw new \UnexpectedValueException('$class_storage cannot be empty for ' . $function_id);
            }

            $class_storage = ClassLikeChecker::$storage[$fq_classlike_name_lc];

            if (isset($class_storage->methods[strtolower($stmt->name)])) {
                throw new \InvalidArgumentException('Cannot re-register ' . $function_id);
            }

            $storage = $class_storage->methods[strtolower($stmt->name)] = new MethodStorage();

            $class_name_parts = explode('\\', $fq_classlike_name);
            $class_name = array_pop($class_name_parts);

            if (strtolower((string)$stmt->name) === strtolower($class_name) &&
                !isset($class_storage->methods['__construct']) &&
                strpos($fq_classlike_name, '\\') === false
            ) {
                MethodChecker::setDeclaringMethodId($fq_classlike_name . '::__construct', $function_id);
                MethodChecker::setAppearingMethodId($fq_classlike_name . '::__construct', $function_id);
            }

            $class_storage->declaring_method_ids[strtolower($stmt->name)] = $function_id;
            $class_storage->appearing_method_ids[strtolower($stmt->name)] = $function_id;

            if (!isset($class_storage->overridden_method_ids[strtolower($stmt->name)])) {
                $class_storage->overridden_method_ids[strtolower($stmt->name)] = [];
            }

            /** @var bool */
            $storage->is_static = $stmt->isStatic();

            /** @var bool */
            $storage->abstract = $stmt->isAbstract();

            if ($stmt->isPrivate()) {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PRIVATE;
            } elseif ($stmt->isProtected()) {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PROTECTED;
            } else {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;
            }
        } else {
            $file_storage = FileChecker::$storage[strtolower($this->file_path)];

            $function_id = $cased_function_id = $this->file_path . ':' . $stmt->getLine() . ':' . 'closure';

            $storage = $file_storage->functions[$function_id] = new FunctionLikeStorage();
        }

        $this->functionlike_storage = $storage;

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod || $stmt instanceof PhpParser\Node\Stmt\Function_) {
            $storage->cased_name = $stmt->name;
        }

        $storage->location = new CodeLocation($this->file_checker, $stmt, null, true);

        $required_param_count = 0;
        $i = 0;
        $has_optional_param = false;

        /** @var PhpParser\Node\Param $param */
        foreach ($stmt->getParams() as $param) {
            $param_array = $this->getTranslatedFunctionParam($param);

            if (isset($storage->param_types[$param_array->name])) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Duplicate param $' . $param->name . ' in docblock for ' . $cased_function_id,
                        new CodeLocation($this->file_checker, $param, null, true)
                    )
                )) {
                    // fall through
                }
            }

            $storage->param_types[$param_array->name] = $param_array->type;
            $storage->params[] = $param_array;

            if (!$param_array->is_optional) {
                $required_param_count = $i + 1;

                if (!$param->variadic && $has_optional_param) {
                    if (IssueBuffer::accepts(
                        new MisplacedRequiredParam(
                            'Required param $' . $param->name . ' should come before any optional params in ' .
                            $cased_function_id,
                            new CodeLocation($this->file_checker, $param, null, true)
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

        if ($parser_return_type = $stmt->getReturnType()) {
            $suffix = '';

            if ($parser_return_type instanceof PhpParser\Node\NullableType) {
                $suffix = '|null';
                $parser_return_type = $parser_return_type->type;
            }

            if (is_string($parser_return_type)) {
                $return_type_string = $parser_return_type . $suffix;
            } else {
                $return_type_fq_classlike_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $parser_return_type,
                    $this->aliases
                );

                if (!in_array($return_type_fq_classlike_name, ['self', 'static', 'parent'], true)) {
                    $this->project_checker->queueClassLikeForScanning($return_type_fq_classlike_name);
                }

                $return_type_string = $return_type_fq_classlike_name . $suffix;
            }

            $storage->return_type = Type::parseString($return_type_string);
            $storage->return_type_location = new CodeLocation(
                $this->file_checker,
                $stmt,
                null,
                false,
                FunctionLikeChecker::RETURN_TYPE_REGEX
            );

            $storage->return_type->queueClassLikesForScanning($this->project_checker);

            $storage->signature_return_type = $storage->return_type;
            $storage->signature_return_type_location = $storage->return_type_location;
        }

        $docblock_info = null;
        $doc_comment = $stmt->getDocComment();

        if (!$doc_comment) {
            return;
        }

        try {
            $docblock_info = CommentChecker::extractFunctionDocblockInfo(
                (string)$doc_comment,
                $doc_comment->getLine()
            );
        } catch (DocblockParseException $e) {
            if (IssueBuffer::accepts(
                new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_checker, $stmt, null, true)
                )
            )) {
                // fall through
            }
        }

        if (!$docblock_info) {
            return;
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

        $storage->suppressed_issues = $docblock_info->suppress;

        if (!$this->config->use_docblock_types) {
            return;
        }

        $template_types = $class_storage && $class_storage->template_types ? $class_storage->template_types : null;

        if ($docblock_info->template_types) {
            $storage->template_types = [];

            foreach ($docblock_info->template_types as $template_type) {
                if (count($template_type) === 3) {
                    $as_type_string = ClassLikeChecker::getFQCLNFromString($template_type[2], $this->aliases);
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

        if ($docblock_info->return_type) {
            if (!$storage->return_type || (string)$docblock_info->return_type !== (string)$storage->return_type) {
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
                    $storage->return_type_location = new CodeLocation($this->file_checker, $stmt, null, true);
                }

                if ($docblock_return_type) {
                    $fixed_type_string = FunctionLikeChecker::fixUpLocalType(
                        $docblock_return_type,
                        $this->aliases,
                        $this->function_template_types + $this->class_template_types
                    );

                    try {
                        $storage->return_type = Type::parseString($fixed_type_string);
                        $storage->return_type->setFromDocblock();
                        $storage->return_type->queueClassLikesForScanning($this->project_checker);
                    } catch (\Psalm\Exception\TypeParseTreeException $e) {
                        if (IssueBuffer::accepts(
                            new InvalidDocblock(
                                $e->getMessage() . ' in docblock for ' . $cased_function_id,
                                new CodeLocation($this->file_checker, $stmt, null, true)
                            )
                        )) {
                            // fall through
                        }
                    }
                }

                if ($storage->return_type && $docblock_info->ignore_nullable_return) {
                    $storage->return_type->ignore_nullable_issues = true;
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
                $template_types,
                $stmt,
                new CodeLocation($this->file_checker, $stmt, null, true)
            );
        }
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
            if (is_string($param_typehint)) {
                $param_type_string = $param_typehint;
            } elseif ($param_typehint instanceof PhpParser\Node\Name\FullyQualified) {
                $param_type_string = (string)$param_typehint;
                $this->project_checker->queueClassLikeForScanning($param_type_string);
            } elseif ($param_typehint->parts === ['self']) {
                $param_type_string = $this->fq_classlike_name;
            } else {
                $param_type_string = ClassLikeChecker::getFQCLNFromNameObject($param_typehint, $this->aliases);
                if (!in_array($param_type_string, ['self', 'static', 'parent'], true)) {
                    $this->project_checker->queueClassLikeForScanning($param_type_string);
                }
            }

            if ($param_type_string) {
                if ($is_nullable) {
                    $param_type_string .= '|null';
                }

                $param_type = Type::parseString($param_type_string);

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

        return new FunctionLikeParameter(
            $param->name,
            $param->byRef,
            $param_type ?: Type::getMixed(),
            new CodeLocation($this->file_checker, $param, null, false, FunctionLikeChecker::PARAM_TYPE_REGEX),
            $is_optional,
            $is_nullable,
            $param->variadic
        );
    }

    /**
     * @param  array<int, array{type:string,name:string,line_number:int}>  $docblock_params
     * @param  FunctionLikeStorage          $storage
     * @param  array<string, string>|null   $template_types
     * @param  PhpParser\Node\FunctionLike  $function
     * @param  string|null                  $fq_classlike_name
     * @param  CodeLocation                 $code_location
     *
     * @return void
     */
    protected function improveParamsFromDocblock(
        FunctionLikeStorage $storage,
        array $docblock_params,
        $template_types,
        $function,
        CodeLocation $code_location
    ) {
        $docblock_param_vars = [];

        $base = $this->fq_classlike_name ? $this->fq_classlike_name . '::' : '';

        $cased_method_id = $base . $storage->cased_name;

        $file_checker = $this->file_checker;

        foreach ($docblock_params as $docblock_param) {
            $param_name = $docblock_param['name'];
            $line_number = $docblock_param['line_number'];
            $docblock_param_variadic = false;

            if (substr($param_name, 0, 3) === '...') {
                $docblock_param_variadic = true;
                $param_name = substr($param_name, 3);
            }

            $param_name = substr($param_name, 1);

            if (!isset($storage->param_types[$param_name])) {
                continue;
            }

            $storage_param = null;

            foreach ($storage->params as $function_signature_param) {
                if ($function_signature_param->name === $param_name) {
                    $storage_param = $function_signature_param;
                    break;
                }
            }

            if ($storage_param === null) {
                throw new \UnexpectedValueException('This should not be possible');
            }

            $storage_param_type = $storage->param_types[$param_name];

            $docblock_param_vars[$param_name] = true;

            $new_param_type = Type::parseString(
                FunctionLikeChecker::fixUpLocalType(
                    $docblock_param['type'],
                    $this->aliases,
                    $this->function_template_types + $this->class_template_types
                )
            );

            $new_param_type->queueClassLikesForScanning($this->project_checker, $storage->template_types ?: []);

            if ($docblock_param_variadic) {
                $new_param_type = new Type\Union([
                    new Type\Atomic\TArray([
                        Type::getInt(),
                        $new_param_type,
                    ]),
                ]);
            }

            $existing_param_type_nullable = $storage_param->is_nullable;

            $new_param_type->setFromDocblock();

            if ($storage_param->type->isMixed() || $storage->template_types) {
                if ($existing_param_type_nullable && !$new_param_type->isNullable()) {
                    $new_param_type->types['null'] = new Type\Atomic\TNull();
                }

                $storage_param->type = $new_param_type;
                $storage_param->location = $code_location;
                continue;
            }

            if ((string)$storage_param->type === (string)$new_param_type) {
                continue;
            }

            $improved_atomic_types = [];

            foreach ($storage_param->type->types as $key => $atomic_type) {
                if (!isset($new_param_type->types[$key])) {
                    $improved_atomic_types[$key] = clone $atomic_type;
                    continue;
                }

                $improved_atomic_types[$key] = clone $new_param_type->types[$key];
            }

            if ($existing_param_type_nullable && !isset($improved_atomic_types['null'])) {
                $improved_atomic_types['null'] = new Type\Atomic\TNull();
            }

            $storage_param->type = new Type\Union(array_values($improved_atomic_types));
            $storage_param->type->setFromDocblock();
            $storage_param->location = $code_location;
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\Property    $stmt
     * @param   Context                         $class_context
     * @param   Config                          $config
     *
     * @return  void
     */
    private function visitPropertyDeclaration(
        PhpParser\Node\Stmt\Property $stmt,
        Config $config
    ) {
        if (!$this->fq_classlike_name) {
            throw new \LogicException('$this->fq_classlike_name should bot be null');
        }

        $comment = $stmt->getDocComment();
        $var_comment = null;
        $property_type_line_number = null;
        $storage = ClassLikeChecker::$storage[strtolower($this->fq_classlike_name)];

        if ($comment && $comment->getText() && $config->use_docblock_types) {
            try {
                $property_type_line_number = $comment->getLine();
                $var_comment = CommentChecker::getTypeFromComment(
                    $comment->getText(),
                    null,
                    $this->file_checker,
                    $this->aliases,
                    $this->function_template_types + $this->class_template_types,
                    $property_type_line_number
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($this->file_checker, $stmt, null, true)
                    )
                )) {
                    // fall through
                }
            }
        }

        $property_group_type = $var_comment ? Type::parseString($var_comment->type) : null;

        if ($property_group_type) {
            $property_group_type->queueClassLikesForScanning($this->project_checker);
            $property_group_type->setFromDocblock();
        }

        foreach ($stmt->props as $property) {
            $property_type_location = null;
            $default_type = null;

            if (!$property_group_type) {
                if ($property->default) {
                    $default_type = StatementsChecker::getSimpleType($property->default);

                    if (!$config->use_property_default_for_type) {
                        $property_type = false;
                    } else {
                        $property_type = $default_type ?: Type::getMixed();
                    }
                } else {
                    $property_type = false;
                }
            } else {
                if ($property_type_line_number) {
                    $property_type_location = new CodeLocation($this->file_checker, $stmt);
                    $property_type_location->setCommentLine($property_type_line_number);
                }

                $property_type = count($stmt->props) === 1 ? $property_group_type : clone $property_group_type;
            }

            $property_storage = $storage->properties[$property->name] = new PropertyStorage();
            $property_storage->is_static = (bool)$stmt->isStatic();
            $property_storage->type = $property_type;
            $property_storage->location = new CodeLocation($this->file_checker, $property);
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

            $property_id = $this->fq_classlike_name . '::$' . $property->name;

            $storage->declaring_property_ids[$property->name] = $property_id;
            $storage->appearing_property_ids[$property->name] = $property_id;

            if (!$stmt->isPrivate()) {
                $storage->inheritable_property_ids[$property->name] = $property_id;
            }
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\ClassConst  $stmt
     * @param   Config                          $config
     *
     * @return  void
     */
    private function visitClassConstDeclaration(PhpParser\Node\Stmt\ClassConst $stmt)
    {
        if (!$this->fq_classlike_name) {
            throw new \LogicException('$this->fq_classlike_name should bot be null');
        }

        $storage = ClassLikeChecker::$storage[strtolower($this->fq_classlike_name)];

        $existing_constants = $storage->protected_class_constants
            + $storage->private_class_constants
            + $storage->public_class_constants;

        foreach ($stmt->consts as $const) {
            $const_type = StatementsChecker::getSimpleType(
                $const->value,
                $this->file_checker,
                $existing_constants
            ) ?: Type::getMixed();

            $existing_constants[$const->name] = $const_type;

            if ($stmt->isProtected()) {
                $storage->protected_class_constants[$const->name] = $const_type;
            } elseif ($stmt->isPrivate()) {
                $storage->private_class_constants[$const->name] = $const_type;
            } else {
                $storage->public_class_constants[$const->name] = $const_type;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Include_ $stmt
     *
     * @return false|null
     */
    public function visitInclude(PhpParser\Node\Expr\Include_ $stmt)
    {
        $config = Config::getInstance();

        if (!$config->allow_includes) {
            throw new FileIncludeException(
                'File includes are not allowed per your Psalm config - check the allowFileIncludes flag.'
            );
        }

        $path_to_file = null;

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = $stmt->expr->value;

            // attempts to resolve using get_include_path dirs
            $include_path = StatementsChecker::resolveIncludePath($path_to_file, dirname($this->file_path));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if ($path_to_file[0] !== DIRECTORY_SEPARATOR) {
                $path_to_file = getcwd() . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = StatementsChecker::getPathTo($stmt->expr, $this->file_path);
        }

        if ($path_to_file) {
            $reduce_pattern = '/\/[^\/]+\/\.\.\//';

            while (preg_match($reduce_pattern, $path_to_file)) {
                $path_to_file = preg_replace($reduce_pattern, DIRECTORY_SEPARATOR, $path_to_file);
            }

            // if the file is already included, we can't check much more
            if (in_array($path_to_file, get_included_files(), true)) {
                return null;
            }

            if ($this->file_path === $path_to_file) {
                return null;
            }

            if ($this->project_checker->fileExists($path_to_file)) {
                $this->project_checker->queueFileForScanning($path_to_file);

                $file_storage = FileChecker::$storage[strtolower($this->file_path)];
                $file_storage->included_file_paths[strtolower($path_to_file)] = $path_to_file;

                return null;
            }
        }

        return null;
    }
}
