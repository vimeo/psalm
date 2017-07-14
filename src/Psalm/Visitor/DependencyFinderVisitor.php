<?php
namespace Psalm\Visitor;

use PhpParser;
use Psalm\Aliases;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Exception\DocblockParseException;
use Psalm\FunctionLikeParameter;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MisplacedRequiredParam;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;

class DependencyFinderVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /** @var Aliases */
    protected $aliases;

    /** @var Aliases */
    protected $file_aliases;

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
    protected $will_analyze;

    /** @var Config */
    protected $config;

    /**
     * @param ProjectChecker $project_checker
     * @param string $file_path
     */
    public function __construct(ProjectChecker $project_checker, FileChecker $file_checker)
    {
        $this->project_checker = $project_checker;
        $this->file_checker = $file_checker;
        $this->file_path = $file_checker->getFilePath();
        $this->will_analyze = $file_checker->will_analyze;
        $this->config = Config::getInstance();
        $this->aliases = new Aliases();
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
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike && $node->name) {
            $fq_class_name = ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $node->name;

            $fq_class_name_lc = strtolower($fq_class_name);

            ClassLikeChecker::$storage[$fq_class_name_lc] = $storage = new ClassLikeStorage();
            $storage->name = $fq_class_name;
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
                                $as_type_string = $this->getFQCLNFromString($template_type[2]);
                                $storage->template_types[$template_type[0]] = $as_type_string;
                            } else {
                                $storage->template_types[$template_type[0]] = 'mixed';
                            }
                        }
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

                $this->project_checker->addFullyQualifiedClassName($fq_class_name, $this->file_path);

                if ($node->extends) {
                    $parent_fqcln = ClassLikeChecker::getFQCLNFromNameObject($node->extends, $this->aliases);
                    $this->project_checker->queueClassLikeForScanning($parent_fqcln, true);
                    $storage->parent_classes[] = strtolower($parent_fqcln);
                }

                foreach ($node->implements as $interface) {
                    $interface_fqcln = ClassLikeChecker::getFQCLNFromNameObject($interface, $this->aliases);
                    $this->project_checker->queueClassLikeForScanning($interface_fqcln);
                    $storage->class_implements[] = $interface_fqcln;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Interface_) {
                $this->project_checker->addFullyQualifiedInterfaceName($fq_class_name, $this->file_path);

                foreach ($node->extends as $interface) {
                    $interface_fqcln = ClassLikeChecker::getFQCLNFromNameObject($interface, $this->aliases);
                    $this->project_checker->queueClassLikeForScanning($interface_fqcln);
                    $storage->parent_interfaces[] = $interface_fqcln;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Trait_) {
                $this->project_checker->addFullyQualifiedTraitName($fq_class_name, $this->file_path);
            }

            $this->fq_classlike_name = $fq_class_name;
        } elseif (($node instanceof PhpParser\Node\Expr\New_
                || $node instanceof PhpParser\Node\Expr\Instanceof_
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                || $node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\StaticCall)
            && $node->class instanceof PhpParser\Node\Name
        ) {
            $fq_classlike_name = ClassLikeChecker::getFQCLNFromNameObject($node->class, $this->aliases);

            if (!in_array($fq_classlike_name, ['self', 'static', 'parent'])) {
                $this->project_checker->queueClassLikeForScanning($fq_classlike_name);
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TryCatch) {
            foreach ($node->catches as $catch) {
                foreach ($catch->types as $catch_type) {
                    $catch_fqcln = ClassLikeChecker::getFQCLNFromNameObject($catch_type, $this->aliases);

                    if (!in_array($catch_fqcln, ['self', 'static', 'parent'])) {
                        $this->project_checker->queueClassLikeForScanning($catch_fqcln);
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            $this->registerFunctionLike($node);

            if (!$this->will_analyze) {
                return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TraitUse) {
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
                $this->project_checker->queueClassLikeForScanning($trait_fqcln, true);
                $storage->used_traits[$trait_fqcln] = true;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Property) {
            $this->visitPropertyDeclaration($node, $this->config);
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassConst) {
            $storage = ClassLikeChecker::$storage[strtolower($this->fq_classlike_name)];

            foreach ($node->consts as $const) {
                if ($node->isProtected()) {
                    $storage->protected_class_constants[$const->name] = Type::getMixed();
                } elseif ($node->isPrivate()) {
                    $storage->private_class_constants[$const->name] = Type::getMixed();
                } else {
                    $storage->public_class_constants[$const->name] = Type::getMixed();
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassConst) {
            $this->visitClassConstDeclaration($node, $this->config);
        }
    }

    public function leaveNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->aliases = $this->file_aliases;
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike && $node->name) {
            $this->fq_classlike_name = null;
        }
    }

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
                $file_storage = FileChecker::$storage[$this->file_path];

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id];
                }

                $storage = $file_storage->functions[$function_id] = new FunctionLikeStorage();
            }
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            $fq_class_name = $this->fq_classlike_name;

            $function_id = $fq_class_name . '::' . strtolower($stmt->name);
            $cased_function_id = $fq_class_name . '::' . $stmt->name;

            $fq_class_name_lower = strtolower($fq_class_name);

            if (!isset(ClassLikeChecker::$storage[$fq_class_name_lower])) {
                throw new \UnexpectedValueException('$class_storage cannot be empty for ' . $function_id);
            }

            $class_storage = ClassLikeChecker::$storage[$fq_class_name_lower];

            if (isset($class_storage->methods[strtolower($stmt->name)])) {
                throw new \InvalidArgumentException('Cannot re-register ' . $function_id);
            }

            $storage = $class_storage->methods[strtolower($stmt->name)] = new MethodStorage();

            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (strtolower((string)$stmt->name) === strtolower($class_name) &&
                !isset($class_storage->methods['__construct']) &&
                strpos($fq_class_name, '\\') === false
            ) {
                MethodChecker::setDeclaringMethodId($fq_class_name . '::__construct', $function_id);
                MethodChecker::setAppearingMethodId($fq_class_name . '::__construct', $function_id);
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
            $file_storage = FileChecker::$storage[$this->file_path];

            $function_id = $cased_function_id = $this->file_path . ':' . $stmt->getLine() . ':' . 'closure';

            $storage = $file_storage->functions[$function_id] = new FunctionLikeStorage();
        }

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
                $return_type_fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $parser_return_type,
                    $this->aliases
                );

                if (!in_array($return_type_fq_class_name, ['self', 'static', 'parent'])) {
                    $this->project_checker->queueClassLikeForScanning($return_type_fq_class_name);
                }

                $return_type_string = $return_type_fq_class_name . $suffix;
            }

            $storage->return_type = Type::parseString($return_type_string);
            $storage->return_type_location = new CodeLocation(
                $this->file_checker,
                $stmt,
                null,
                false,
                FunctionLikeChecker::RETURN_TYPE_REGEX
            );

            $storage->signature_return_type = $storage->return_type;
            $storage->signature_return_type_location = $storage->return_type_location;
        }

        $docblock_info = null;
        $doc_comment = $stmt->getDocComment();

        if (!$doc_comment) {
            return $storage;
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

        $storage->suppressed_issues = $docblock_info->suppress;

        if (!$this->config->use_docblock_types) {
            return $storage;
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
                    $storage->return_type = Type::parseString(
                        FunctionLikeChecker::fixUpLocalType($docblock_return_type, $this->aliases)
                    );
                    $storage->return_type->setFromDocblock();
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
    public function getTranslatedFunctionParam(PhpParser\Node\Param $param) {
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
                if (!in_array($param_type_string, ['self', 'static', 'parent'])) {
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
     * @param  Closure|Function_|ClassMethod $function
     * @param  string|null                  $fq_class_name
     * @param  CodeLocation                 $code_location
     *
     * @return false|null
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
                FunctionLikeChecker::fixUpLocalType($docblock_param['type'], $this->aliases)
            );

            if ($docblock_param_variadic) {
                $new_param_type = new Type\Union([
                    new Type\Atomic\TArray([
                        Type::getInt(),
                        $new_param_type,
                    ]),
                ]);
            }

            $new_param_type->setFromDocblock();

            $existing_param_type_nullable = $storage_param->is_nullable;

            if ($existing_param_type_nullable && !$new_param_type->isNullable()) {
                $new_param_type->types['null'] = new Type\Atomic\TNull();
            }

            if ((string)$storage_param->type !== (string)$new_param_type) {
                $storage_param->type = $new_param_type;
            }
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
                    [],
                    $property_type_line_number
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($this, $this->class, null, true)
                    )
                )) {
                    // fall through
                }
            }
        }

        $property_group_type = $var_comment ? $var_comment->type : null;

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
    private function visitClassConstDeclaration(
        PhpParser\Node\Stmt\ClassConst $stmt,
        Config $config
    ) {
        $comment = $stmt->getDocComment();
        $var_comment = null;
        $storage = ClassLikeChecker::$storage[strtolower((string)$class_context->self)];

        if ($comment && $config->use_docblock_types && count($stmt->consts) === 1) {
            $var_comment = CommentChecker::getTypeFromComment(
                (string) $comment,
                null,
                $this->aliases,
                $this->file_checker
            );
        }

        $const_type = $var_comment ? $var_comment->type : Type::getMixed();

        foreach ($stmt->consts as $const) {
            if ($stmt->isProtected()) {
                $storage->protected_class_constants[$const->name] = $const_type;
            } elseif ($stmt->isPrivate()) {
                $storage->private_class_constants[$const->name] = $const_type;
            } else {
                $storage->public_class_constants[$const->name] = $const_type;
            }
        }
    }
}
