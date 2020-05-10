<?php
namespace Psalm\Internal\PhpVisitor;

use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function assert;
use function class_exists;
use function count;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function end;
use function explode;
use function function_exists;
use function implode;
use function in_array;
use function interface_exists;
use function is_string;
use PhpParser;
use function preg_match;
use function preg_replace;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\FileIncludeException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Internal\Codebase\PropertyMap;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Internal\Scanner\PhpStormMetaScanner;
use Psalm\Internal\Scanner\UnresolvedConstant;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\DuplicateFunction;
use Psalm\Issue\DuplicateMethod;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MissingDocblockType;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * @internal
 */
class ReflectorVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor, FileSource
{
    /** @var Aliases */
    private $aliases;

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

    /** @var array<string, array<string, array{Type\Union}>> */
    private $class_template_types = [];

    /** @var array<string, array<string, array{Type\Union}>> */
    private $function_template_types = [];

    /** @var FunctionLikeStorage[] */
    private $functionlike_storages = [];

    /** @var FileStorage */
    private $file_storage;

    /** @var ClassLikeStorage[] */
    private $classlike_storages = [];

    /** @var class-string<\Psalm\Plugin\Hook\AfterClassLikeVisitInterface>[] */
    private $after_classlike_check_plugins;

    /** @var int */
    private $php_major_version;

    /** @var int */
    private $php_minor_version;

    /** @var PhpParser\Node\Name|null */
    private $namespace_name;

    /** @var PhpParser\Node\Expr|null */
    private $exists_cond_expr;

    /**
     * @var ?int
     */
    private $skip_if_descendants = null;

    /**
     * @var array<string, array<int, array{0: string, 1: int}>>
     */
    private $type_aliases = [];

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
        $this->file_storage = $file_storage;
        $this->aliases = $this->file_storage->aliases = new Aliases();
        $this->after_classlike_check_plugins = $this->config->after_visit_classlikes;
        $this->php_major_version = $codebase->php_major_version;
        $this->php_minor_version = $codebase->php_minor_version;
    }

    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        foreach ($node->getComments() as $comment) {
            if ($comment instanceof PhpParser\Comment\Doc) {
                try {
                    $type_alias_tokens = CommentAnalyzer::getTypeAliasesFromComment(
                        $comment,
                        $this->aliases,
                        $this->type_aliases
                    );

                    foreach ($type_alias_tokens as $type_tokens) {
                        // finds issues, if there are any
                        Type::parseTokens($type_tokens);
                    }

                    $this->type_aliases += $type_alias_tokens;
                } catch (DocblockParseException $e) {
                    $this->file_storage->docblock_issues[] = new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($this->file_scanner, $node, null, true)
                    );
                } catch (TypeParseTreeException $e) {
                    $this->file_storage->docblock_issues[] = new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($this->file_scanner, $node, null, true)
                    );
                }
            }
        }

        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->file_storage->aliases = $this->aliases;

            $this->namespace_name = $node->name;

            $this->aliases = new Aliases(
                $node->name ? implode('\\', $node->name->parts) : '',
                $this->aliases->uses,
                $this->aliases->functions,
                $this->aliases->constants,
                $this->aliases->uses_flipped,
                $this->aliases->functions_flipped,
                $this->aliases->constants_flipped
            );

            $this->file_storage->namespace_aliases[(int) $node->getAttribute('startFilePos')] = $this->aliases;

            if ($node->stmts) {
                $this->aliases->namespace_first_stmt_start = (int) $node->stmts[0]->getAttribute('startFilePos');
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $use_path = implode('\\', $use->name->parts);

                $use_alias = $use->alias ? $use->alias->name : $use->name->getLast();

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliases->functions[strtolower($use_alias)] = $use_path;
                        $this->aliases->functions_flipped[strtolower($use_path)] = $use_alias;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliases->constants[$use_alias] = $use_path;
                        $this->aliases->constants_flipped[$use_path] = $use_alias;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliases->uses[strtolower($use_alias)] = $use_path;
                        $this->aliases->uses_flipped[strtolower($use_path)] = $use_alias;
                        break;
                }
            }

            if (!$this->aliases->uses_start) {
                $this->aliases->uses_start = (int) $node->getAttribute('startFilePos');
            }

            $this->aliases->uses_end = (int) $node->getAttribute('endFilePos') + 1;
        } elseif ($node instanceof PhpParser\Node\Stmt\GroupUse) {
            $use_prefix = implode('\\', $node->prefix->parts);

            foreach ($node->uses as $use) {
                $use_path = $use_prefix . '\\' . implode('\\', $use->name->parts);
                $use_alias = $use->alias ? $use->alias->name : $use->name->getLast();

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliases->functions[strtolower($use_alias)] = $use_path;
                        $this->aliases->functions_flipped[strtolower($use_path)] = $use_alias;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliases->constants[$use_alias] = $use_path;
                        $this->aliases->constants_flipped[$use_path] = $use_alias;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliases->uses[strtolower($use_alias)] = $use_path;
                        $this->aliases->uses_flipped[strtolower($use_path)] = $use_alias;
                        break;
                }
            }

            if (!$this->aliases->uses_start) {
                $this->aliases->uses_start = (int) $node->getAttribute('startFilePos');
            }

            $this->aliases->uses_end = (int) $node->getAttribute('endFilePos') + 1;
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if ($this->skip_if_descendants) {
                return;
            }

            if ($this->registerClassLike($node) === false) {
                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
            }
        } elseif (($node instanceof PhpParser\Node\Expr\New_
                || $node instanceof PhpParser\Node\Expr\Instanceof_
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                || $node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\StaticCall)
            && $node->class instanceof PhpParser\Node\Name
        ) {
            $fq_classlike_name = ClassLikeAnalyzer::getFQCLNFromNameObject($node->class, $this->aliases);

            if (!in_array(strtolower($fq_classlike_name), ['self', 'static', 'parent'], true)) {
                $this->codebase->scanner->queueClassLikeForScanning(
                    $fq_classlike_name,
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
                    $catch_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($catch_type, $this->aliases);

                    if (!in_array(strtolower($catch_fqcln), ['self', 'static', 'parent'], true)) {
                        $this->codebase->scanner->queueClassLikeForScanning($catch_fqcln);
                        $this->file_storage->referenced_classlikes[strtolower($catch_fqcln)] = $catch_fqcln;
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            if ($node instanceof PhpParser\Node\Stmt\Function_
                || $node instanceof PhpParser\Node\Stmt\ClassMethod
            ) {
                if ($this->skip_if_descendants) {
                    return;
                }
            }

            $this->registerFunctionLike($node);

            if ($node instanceof PhpParser\Node\Expr\Closure) {
                $this->codebase->scanner->queueClassLikeForScanning('Closure');
            }

            if (!$this->scan_deep) {
                return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Global_) {
            $function_like_storage = end($this->functionlike_storages);

            if ($function_like_storage) {
                foreach ($node->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name) && $var->name !== 'argv' && $var->name !== 'argc') {
                            $var_id = '$' . $var->name;

                            $function_like_storage->global_variables[$var_id] = true;
                        }
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Expr\FuncCall && $node->name instanceof PhpParser\Node\Name) {
            $function_id = implode('\\', $node->name->parts);
            if (CallMap::inCallMap($function_id)) {
                $this->registerClassMapFunctionCall($function_id, $node);
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TraitUse) {
            if ($this->skip_if_descendants) {
                return;
            }

            if (!$this->classlike_storages) {
                throw new \LogicException('$this->classlike_storages should not be empty');
            }

            $storage = $this->classlike_storages[count($this->classlike_storages) - 1];

            $method_map = $storage->trait_alias_map ?: [];
            $visibility_map = $storage->trait_visibility_map ?: [];

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                    $old_name = strtolower($adaptation->method->name);
                    $new_name = $old_name;

                    if ($adaptation->newName) {
                        $new_name = strtolower($adaptation->newName->name);

                        if ($new_name !== $old_name) {
                            $method_map[$new_name] = $old_name;
                        }
                    }

                    if ($adaptation->newModifier) {
                        switch ($adaptation->newModifier) {
                            case 1:
                                $visibility_map[$new_name] = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
                                break;

                            case 2:
                                $visibility_map[$new_name] = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
                                break;

                            case 4:
                                $visibility_map[$new_name] = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
                                break;
                        }
                    }
                }
            }

            $storage->trait_alias_map = $method_map;
            $storage->trait_visibility_map = $visibility_map;

            foreach ($node->traits as $trait) {
                $trait_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($trait, $this->aliases);
                $this->codebase->scanner->queueClassLikeForScanning($trait_fqcln, $this->scan_deep);
                $storage->used_traits[strtolower($trait_fqcln)] = $trait_fqcln;
                $this->file_storage->required_classes[strtolower($trait_fqcln)] = $trait_fqcln;
            }

            if ($node_comment = $node->getDocComment()) {
                $comments = DocComment::parsePreservingLength($node_comment);

                if (isset($comments['specials']['template-use'])
                    || isset($comments['specials']['use'])
                    || isset($comments['specials']['phpstan-use'])
                    || isset($comments['specials']['psalm-use'])
                ) {
                    $all_inheritance = ($comments['specials']['template-use'] ?? [])
                        + ($comments['specials']['use'] ?? [])
                        + ($comments['specials']['phpstan-use'] ?? [])
                        + ($comments['specials']['psalm-use'] ?? []);

                    foreach ($all_inheritance as $template_line) {
                        $this->useTemplatedType(
                            $storage,
                            $node,
                            trim(preg_replace('@^[ \t]*\*@m', '', $template_line))
                        );
                    }
                }

                if (isset($comments['specials']['template-extends'])
                    || isset($comments['specials']['extends'])
                    || isset($comments['specials']['template-implements'])
                    || isset($comments['specials']['implements'])
                ) {
                    $storage->docblock_issues[] = new InvalidDocblock(
                        'You must use @use or @template-use to parameterize traits',
                        new CodeLocation($this->file_scanner, $node, null, true)
                    );
                }
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Include_) {
            $this->visitInclude($node);
        } elseif ($node instanceof PhpParser\Node\Expr\Assign
            || $node instanceof PhpParser\Node\Expr\AssignOp
            || $node instanceof PhpParser\Node\Expr\AssignRef
            || $node instanceof PhpParser\Node\Stmt\For_
            || $node instanceof PhpParser\Node\Stmt\Foreach_
            || $node instanceof PhpParser\Node\Stmt\While_
            || $node instanceof PhpParser\Node\Stmt\Do_
        ) {
            if ($doc_comment = $node->getDocComment()) {
                $var_comments = [];

                try {
                    $var_comments = CommentAnalyzer::getTypeFromComment(
                        $doc_comment,
                        $this->file_scanner,
                        $this->aliases,
                        null,
                        $this->type_aliases
                    );
                } catch (DocblockParseException $e) {
                    // do nothing
                }

                foreach ($var_comments as $var_comment) {
                    if (!$var_comment->type) {
                        continue;
                    }

                    $var_type = $var_comment->type;
                    $var_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
                }
            }

            if ($node instanceof PhpParser\Node\Expr\Assign
                || $node instanceof PhpParser\Node\Expr\AssignOp
                || $node instanceof PhpParser\Node\Expr\AssignRef
            ) {
                if ($node->var instanceof PhpParser\Node\Expr\PropertyFetch
                    && $node->var->var instanceof PhpParser\Node\Expr\Variable
                    && $node->var->var->name === 'this'
                    && $node->var->name instanceof PhpParser\Node\Identifier
                ) {
                    $functionlike_storage = end($this->functionlike_storages);

                    if ($functionlike_storage instanceof MethodStorage) {
                        $functionlike_storage->this_property_mutations[$node->var->name->name] = true;
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $const_type = StatementsAnalyzer::getSimpleType(
                    $this->codebase,
                    new \Psalm\Internal\Provider\NodeDataProvider(),
                    $const->value,
                    $this->aliases
                ) ?: Type::getMixed();

                $fq_const_name = Type::getFQCLNFromString($const->name->name, $this->aliases);

                if ($this->codebase->register_stub_files || $this->codebase->register_autoload_files) {
                    $this->codebase->addGlobalConstantType($fq_const_name, $const_type);
                }

                $this->file_storage->constants[$fq_const_name] = $const_type;
                $this->file_storage->declaring_constants[$fq_const_name] = $this->file_path;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\If_ && !$this->skip_if_descendants) {
            if (!$this->fq_classlike_names && !$this->functionlike_storages) {
                $this->exists_cond_expr = $node->cond;

                if ($this->enterConditional($this->exists_cond_expr) === false) {
                    // the else node should terminate the agreement
                    $this->skip_if_descendants = $node->else ? $node->else->getLine() : $node->getLine();
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Else_) {
            if ($this->skip_if_descendants === $node->getLine()) {
                $this->skip_if_descendants = null;
                $this->exists_cond_expr = null;
            } elseif (!$this->skip_if_descendants) {
                if ($this->exists_cond_expr && $this->enterConditional($this->exists_cond_expr) === true) {
                    $this->skip_if_descendants = $node->getLine();
                }
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Yield_ || $node instanceof PhpParser\Node\Expr\YieldFrom) {
            $function_like_storage = end($this->functionlike_storages);

            if ($function_like_storage) {
                $function_like_storage->has_yield = true;
            }
        } elseif ($node instanceof PhpParser\Node\Expr\Cast\Object_) {
            $this->codebase->scanner->queueClassLikeForScanning('stdClass', false, false);
            $this->file_storage->referenced_classlikes['stdclass'] = 'stdClass';
        }
    }

    /**
     * @return null
     */
    public function leaveNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            if (!$this->file_storage->aliases) {
                throw new \UnexpectedValueException('File storage liases should not be null');
            }

            $this->aliases = $this->file_storage->aliases;

            if ($this->codebase->register_stub_files
                && $node->name
                && $node->name->parts === ['PHPSTORM_META']
            ) {
                foreach ($node->stmts as $meta_stmt) {
                    if ($meta_stmt instanceof PhpParser\Node\Stmt\Expression
                        && $meta_stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                        && $meta_stmt->expr->name instanceof PhpParser\Node\Name
                        && $meta_stmt->expr->name->parts === ['override']
                        && count($meta_stmt->expr->args) > 1
                    ) {
                        PhpStormMetaScanner::handleOverride($meta_stmt->expr->args, $this->codebase);
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if ($this->skip_if_descendants) {
                return;
            }

            if (!$this->fq_classlike_names) {
                throw new \LogicException('$this->fq_classlike_names should not be empty');
            }

            $fq_classlike_name = array_pop($this->fq_classlike_names);

            if (PropertyMap::inPropertyMap($fq_classlike_name)) {
                $mapped_properties = PropertyMap::getPropertyMap()[strtolower($fq_classlike_name)];

                if (!$this->classlike_storages) {
                    throw new \UnexpectedValueException('$this->classlike_storages cannot be empty');
                }

                $storage = $this->classlike_storages[count($this->classlike_storages) - 1];

                foreach ($mapped_properties as $property_name => $public_mapped_property) {
                    $property_type = Type::parseString($public_mapped_property);

                    $property_type->queueClassLikesForScanning($this->codebase, $this->file_storage);

                    if (!isset($storage->properties[$property_name])) {
                        $storage->properties[$property_name] = new PropertyStorage();
                    }

                    $storage->properties[$property_name]->type = $property_type;

                    $property_id = $fq_classlike_name . '::$' . $property_name;

                    $storage->declaring_property_ids[$property_name] = $fq_classlike_name;
                    $storage->appearing_property_ids[$property_name] = $property_id;
                }
            }

            if (!$this->classlike_storages) {
                throw new \LogicException('$this->classlike_storages should not be empty');
            }

            $classlike_storage = array_pop($this->classlike_storages);

            if ($classlike_storage->has_visitor_issues) {
                $this->file_storage->has_visitor_issues = true;
            }

            if ($node->name) {
                $this->class_template_types = [];
            }

            if ($this->after_classlike_check_plugins) {
                $file_manipulations = [];

                foreach ($this->after_classlike_check_plugins as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterClassLikeVisit(
                        $node,
                        $classlike_storage,
                        $this,
                        $this->codebase,
                        $file_manipulations
                    );
                }
            }

            if (!$this->file_storage->has_visitor_issues) {
                $this->codebase->cacheClassLikeStorage($classlike_storage, $this->file_path);
            }
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            if ($node instanceof PhpParser\Node\Stmt\Function_
                || $node instanceof PhpParser\Node\Stmt\ClassMethod
            ) {
                $this->function_template_types = [];
            }

            if ($this->skip_if_descendants) {
                return;
            }

            if (!$this->functionlike_storages) {
                if ($this->file_storage->has_visitor_issues) {
                    return;
                }

                throw new \UnexpectedValueException(
                    'There should be function storages for line ' . $this->file_path . ':' . $node->getLine()
                );
            }

            $functionlike_storage = array_pop($this->functionlike_storages);

            if ($functionlike_storage->has_visitor_issues) {
                $this->file_storage->has_visitor_issues = true;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\If_ && $node->getLine() === $this->skip_if_descendants) {
            $this->exists_cond_expr = null;
            $this->skip_if_descendants = null;
        } elseif ($node instanceof PhpParser\Node\Stmt\Else_ && $node->getLine() === $this->skip_if_descendants) {
            $this->exists_cond_expr = null;
            $this->skip_if_descendants = null;
        }

        return null;
    }

    private function enterConditional(PhpParser\Node\Expr $expr) : ?bool
    {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            $enter_negated = $this->enterConditional($expr->expr);

            return $enter_negated === null ? null : !$enter_negated;
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $enter_conditional_left = $this->enterConditional($expr->left);
            $enter_conditional_right = $this->enterConditional($expr->right);

            return $enter_conditional_left !== false && $enter_conditional_right !== false;
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $enter_conditional_left = $this->enterConditional($expr->left);
            $enter_conditional_right = $this->enterConditional($expr->right);

            return $enter_conditional_left !== false || $enter_conditional_right !== false;
        }

        if (!$expr instanceof PhpParser\Node\Expr\FuncCall) {
            return null;
        }

        return $this->functionEvaluatesToTrue($expr);
    }

    private function functionEvaluatesToTrue(PhpParser\Node\Expr\FuncCall $function) : ?bool
    {
        if (!$function->name instanceof PhpParser\Node\Name) {
            return null;
        }

        if ($function->name->parts === ['function_exists']
            && isset($function->args[0])
            && $function->args[0]->value instanceof PhpParser\Node\Scalar\String_
            && function_exists($function->args[0]->value->value)
        ) {
            $reflection_function = new \ReflectionFunction($function->args[0]->value->value);

            if ($reflection_function->isInternal()) {
                return true;
            }

            return false;
        }

        if ($function->name->parts === ['class_exists']
            && isset($function->args[0])
        ) {
            $string_value = null;

            if ($function->args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                $string_value = $function->args[0]->value->value;
            } elseif ($function->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
                && $function->args[0]->value->class instanceof PhpParser\Node\Name
                && $function->args[0]->value->name instanceof PhpParser\Node\Identifier
                && strtolower($function->args[0]->value->name->name) === 'class'
            ) {
                $string_value = (string) $function->args[0]->value->class->getAttribute('resolvedName');
            }

            if ($string_value && class_exists($string_value)) {
                $reflection_class = new \ReflectionClass($string_value);

                if ($reflection_class->getFileName() !== $this->file_path) {
                    $this->codebase->scanner->queueClassLikeForScanning(
                        $string_value
                    );

                    return true;
                }
            }

            return false;
        }

        if ($function->name->parts === ['interface_exists']
            && isset($function->args[0])
        ) {
            $string_value = null;

            if ($function->args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                $string_value = $function->args[0]->value->value;
            } elseif ($function->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
                && $function->args[0]->value->class instanceof PhpParser\Node\Name
                && $function->args[0]->value->name instanceof PhpParser\Node\Identifier
                && strtolower($function->args[0]->value->name->name) === 'class'
            ) {
                $string_value = (string) $function->args[0]->value->class->getAttribute('resolvedName');
            }

            if ($string_value && interface_exists($string_value)) {
                $reflection_class = new \ReflectionClass($string_value);

                if ($reflection_class->getFileName() !== $this->file_path) {
                    $this->codebase->scanner->queueClassLikeForScanning(
                        $string_value
                    );

                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * @return void
     */
    private function registerClassMapFunctionCall(
        string $function_id,
        PhpParser\Node\Expr\FuncCall $node
    ) {
        $callables = CallMap::getCallablesFromCallMap($function_id);

        if ($callables) {
            foreach ($callables as $callable) {
                assert($callable->params !== null);

                foreach ($callable->params as $function_param) {
                    if ($function_param->type) {
                        $function_param->type->queueClassLikesForScanning(
                            $this->codebase,
                            $this->file_storage
                        );
                    }
                }

                if ($callable->return_type && !$callable->return_type->hasMixed()) {
                    $callable->return_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
                }
            }
        }

        if ($function_id === 'define') {
            $first_arg_value = isset($node->args[0]) ? $node->args[0]->value : null;
            $second_arg_value = isset($node->args[1]) ? $node->args[1]->value : null;
            if ($first_arg_value && $second_arg_value) {
                $type_provider = new \Psalm\Internal\Provider\NodeDataProvider();
                $const_name = StatementsAnalyzer::getConstName(
                    $first_arg_value,
                    $type_provider,
                    $this->codebase,
                    $this->aliases
                );

                if ($const_name !== null) {
                    $const_type = StatementsAnalyzer::getSimpleType(
                        $this->codebase,
                        $type_provider,
                        $second_arg_value,
                        $this->aliases
                    ) ?: Type::getMixed();

                    if ($this->functionlike_storages && !$this->config->hoist_constants) {
                        $functionlike_storage =
                            $this->functionlike_storages[count($this->functionlike_storages) - 1];
                        $functionlike_storage->defined_constants[$const_name] = $const_type;
                    } else {
                        $this->file_storage->constants[$const_name] = $const_type;
                        $this->file_storage->declaring_constants[$const_name] = $this->file_path;
                    }

                    if ($this->codebase->register_stub_files || $this->codebase->register_autoload_files) {
                        $this->codebase->addGlobalConstantType($const_name, $const_type);
                    }
                }
            }
        }

        $mapping_function_ids = [];

        if (($function_id === 'array_map' && isset($node->args[0]))
            || ($function_id === 'array_filter' && isset($node->args[1]))
        ) {
            $node_arg_value = $function_id === 'array_map' ? $node->args[0]->value : $node->args[1]->value;

            if ($node_arg_value instanceof PhpParser\Node\Scalar\String_
                || $node_arg_value instanceof PhpParser\Node\Expr\Array_
                || $node_arg_value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
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
                        $callable_fqcln
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

        if ($function_id === 'is_a' || $function_id === 'is_subclass_of') {
            $second_arg = $node->args[1]->value ?? null;

            if ($second_arg instanceof PhpParser\Node\Scalar\String_) {
                $this->codebase->scanner->queueClassLikeForScanning(
                    $second_arg->value
                );
            }
        }

        if ($function_id === 'class_alias' && !$this->skip_if_descendants) {
            $first_arg = $node->args[0]->value ?? null;
            $second_arg = $node->args[1]->value ?? null;

            if ($first_arg instanceof PhpParser\Node\Scalar\String_) {
                $first_arg_value = $first_arg->value;
            } elseif ($first_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                && $first_arg->class instanceof PhpParser\Node\Name
                && $first_arg->name instanceof PhpParser\Node\Identifier
                && strtolower($first_arg->name->name) === 'class'
            ) {
                /** @var string */
                $first_arg_value = $first_arg->class->getAttribute('resolvedName');
            } else {
                $first_arg_value = null;
            }

            if ($second_arg instanceof PhpParser\Node\Scalar\String_) {
                $second_arg_value = $second_arg->value;
            } elseif ($second_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                && $second_arg->class instanceof PhpParser\Node\Name
                && $second_arg->name instanceof PhpParser\Node\Identifier
                && strtolower($second_arg->name->name) === 'class'
            ) {
                /** @var string */
                $second_arg_value = $second_arg->class->getAttribute('resolvedName');
            } else {
                $second_arg_value = null;
            }

            if ($first_arg_value !== null && $second_arg_value !== null) {
                $second_arg_value = strtolower($second_arg_value);

                $this->codebase->classlikes->addClassAlias(
                    $first_arg_value,
                    $second_arg_value
                );

                $this->file_storage->classlike_aliases[$second_arg_value] = $first_arg_value;
            }
        }
    }

    /**
     * @return false|null
     */
    private function registerClassLike(PhpParser\Node\Stmt\ClassLike $node)
    {
        $class_location = new CodeLocation($this->file_scanner, $node);
        $name_location = null;

        $storage = null;

        $class_name = null;

        if ($node->name === null) {
            if (!$node instanceof PhpParser\Node\Stmt\Class_) {
                throw new \LogicException('Anonymous classes are always classes');
            }

            $fq_classlike_name = ClassAnalyzer::getAnonymousClassName($node, $this->file_path);
        } else {
            $name_location = new CodeLocation($this->file_scanner, $node->name);

            $fq_classlike_name =
                ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $node->name->name;

            $fq_classlike_name_lc = strtolower($fq_classlike_name);

            $class_name = $node->name->name;

            if ($this->codebase->classlike_storage_provider->has($fq_classlike_name_lc)) {
                $duplicate_storage = $this->codebase->classlike_storage_provider->get($fq_classlike_name_lc);

                if (!$this->codebase->register_stub_files) {
                    if (!$duplicate_storage->stmt_location
                        || $duplicate_storage->stmt_location->file_path !== $this->file_path
                        || $class_location->getHash() !== $duplicate_storage->stmt_location->getHash()
                    ) {
                        if (IssueBuffer::accepts(
                            new DuplicateClass(
                                'Class ' . $fq_classlike_name . ' has already been defined'
                                    . ($duplicate_storage->location
                                        ? ' in ' . $duplicate_storage->location->file_path
                                        : ''),
                                $name_location
                            )
                        )) {
                        }

                        $this->file_storage->has_visitor_issues = true;

                        $duplicate_storage->has_visitor_issues = true;

                        return false;
                    }
                } elseif (!$duplicate_storage->location
                    || $duplicate_storage->location->file_path !== $this->file_path
                    || $class_location->getHash() !== $duplicate_storage->location->getHash()
                ) {
                    // we're overwriting some methods
                    $storage = $duplicate_storage;
                    $this->codebase->classlike_storage_provider->makeNew(strtolower($fq_classlike_name));
                    $storage->populated = false;
                    $storage->class_implements = []; // we do this because reflection reports
                    $storage->parent_interfaces = [];
                    $storage->stubbed = true;
                    $storage->aliases = $this->aliases;

                    foreach ($storage->dependent_classlikes as $dependent_name_lc => $_) {
                        $dependent_storage = $this->codebase->classlike_storage_provider->get($dependent_name_lc);
                        $dependent_storage->populated = false;
                        $this->codebase->classlike_storage_provider->makeNew($dependent_name_lc);
                    }
                }
            }
        }

        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        $this->file_storage->classlikes_in_file[$fq_classlike_name_lc] = $fq_classlike_name;

        $this->fq_classlike_names[] = $fq_classlike_name;

        if (!$storage) {
            $storage = $this->codebase->classlike_storage_provider->create($fq_classlike_name);
        }

        if ($class_name
            && isset($this->aliases->uses[strtolower($class_name)])
            && $this->aliases->uses[strtolower($class_name)] !== $fq_classlike_name
        ) {
            IssueBuffer::add(
                new \Psalm\Issue\ParseError(
                    'Class name ' . $class_name . ' clashes with a use statement alias',
                    $name_location ?: $class_location
                )
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

        $doc_comment = $node->getDocComment();

        $this->classlike_storages[] = $storage;

        if ($node instanceof PhpParser\Node\Stmt\Class_) {
            $storage->abstract = $node->isAbstract();
            $storage->final = $node->isFinal();

            $this->codebase->classlikes->addFullyQualifiedClassName($fq_classlike_name, $this->file_path);

            if ($node->extends) {
                $parent_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($node->extends, $this->aliases);
                $parent_fqcln = $this->codebase->classlikes->getUnAliasedName($parent_fqcln);
                $this->codebase->scanner->queueClassLikeForScanning(
                    $parent_fqcln,
                    $this->scan_deep
                );
                $parent_fqcln_lc = strtolower($parent_fqcln);
                $storage->parent_class = $parent_fqcln;
                $storage->parent_classes[$parent_fqcln_lc] = $parent_fqcln;
                $this->file_storage->required_classes[strtolower($parent_fqcln)] = $parent_fqcln;
            }

            foreach ($node->implements as $interface) {
                $interface_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($interface, $this->aliases);
                $this->codebase->scanner->queueClassLikeForScanning($interface_fqcln);
                $storage->class_implements[strtolower($interface_fqcln)] = $interface_fqcln;
                $storage->direct_class_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
                $this->file_storage->required_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Interface_) {
            $storage->is_interface = true;
            $this->codebase->classlikes->addFullyQualifiedInterfaceName($fq_classlike_name, $this->file_path);

            foreach ($node->extends as $interface) {
                $interface_fqcln = ClassLikeAnalyzer::getFQCLNFromNameObject($interface, $this->aliases);
                $interface_fqcln = $this->codebase->classlikes->getUnAliasedName($interface_fqcln);
                $this->codebase->scanner->queueClassLikeForScanning($interface_fqcln);
                $storage->parent_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
                $storage->direct_interface_parents[strtolower($interface_fqcln)] = $interface_fqcln;
                $this->file_storage->required_interfaces[strtolower($interface_fqcln)] = $interface_fqcln;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Trait_) {
            $storage->is_trait = true;
            $this->file_storage->has_trait = true;
            $this->codebase->classlikes->addFullyQualifiedTraitName($fq_classlike_name, $this->file_path);
        }

        if ($doc_comment) {
            $docblock_info = null;
            try {
                $docblock_info = CommentAnalyzer::extractClassLikeDocblockInfo(
                    $node,
                    $doc_comment,
                    $this->aliases
                );
            } catch (DocblockParseException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . implode('.', $this->fq_classlike_names),
                    $name_location ?: $class_location
                );
            }

            if ($docblock_info) {
                if ($docblock_info->templates) {
                    $storage->template_types = [];

                    \usort(
                        $docblock_info->templates,
                        function (array $l, array $r) : int {
                            return $l[4] > $r[4] ? 1 : -1;
                        }
                    );

                    foreach ($docblock_info->templates as $i => $template_map) {
                        $template_name = $template_map[0];

                        if ($template_map[1] !== null && $template_map[2] !== null) {
                            if (trim($template_map[2])) {
                                try {
                                    $template_type = Type::parseTokens(
                                        Type::fixUpLocalType(
                                            $template_map[2],
                                            $this->aliases,
                                            $storage->template_types,
                                            $this->type_aliases
                                        ),
                                        null,
                                        $storage->template_types
                                    );
                                } catch (TypeParseTreeException $e) {
                                    $storage->docblock_issues[] = new InvalidDocblock(
                                        $e->getMessage() . ' in docblock for '
                                            . implode('.', $this->fq_classlike_names),
                                        $name_location ?: $class_location
                                    );

                                    continue;
                                }

                                $storage->template_types[$template_name] = [
                                    $fq_classlike_name => [$template_type],
                                ];
                            } else {
                                $storage->docblock_issues[] = new InvalidDocblock(
                                    'Template missing as type',
                                    $name_location ?: $class_location
                                );
                            }
                        } else {
                            /** @psalm-suppress PropertyTypeCoercion due to a Psalm bug */
                            $storage->template_types[$template_name][$fq_classlike_name] = [Type::getMixed()];
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
                    $yield_type_tokens = Type::fixUpLocalType(
                        $docblock_info->yield,
                        $this->aliases,
                        $storage->template_types,
                        $this->type_aliases
                    );

                    try {
                        $yield_type = Type::parseTokens(
                            $yield_type_tokens,
                            null,
                            $storage->template_types ?: []
                        );
                        $yield_type->setFromDocblock();
                        $yield_type->queueClassLikesForScanning(
                            $this->codebase,
                            $this->file_storage,
                            $storage->template_types ?: []
                        );

                        $storage->yield = $yield_type;
                    } catch (TypeParseTreeException $e) {
                        // do nothing
                    }
                }

                $storage->sealed_properties = $docblock_info->sealed_properties;
                $storage->sealed_methods = $docblock_info->sealed_methods;

                if ($docblock_info->properties) {
                    foreach ($docblock_info->properties as $property) {
                        $pseudo_property_type_tokens = Type::fixUpLocalType(
                            $property['type'],
                            $this->aliases,
                            null,
                            $this->type_aliases
                        );

                        try {
                            $pseudo_property_type = Type::parseTokens($pseudo_property_type_tokens);
                            $pseudo_property_type->setFromDocblock();
                            $pseudo_property_type->queueClassLikesForScanning(
                                $this->codebase,
                                $this->file_storage,
                                $storage->template_types ?: []
                            );

                            if ($property['tag'] !== 'property-read' && $property['tag'] !== 'psalm-property-read') {
                                $storage->pseudo_property_set_types[$property['name']] = $pseudo_property_type;
                            }

                            if ($property['tag'] !== 'property-write' && $property['tag'] !== 'psalm-property-write') {
                                $storage->pseudo_property_get_types[$property['name']] = $pseudo_property_type;
                            }
                        } catch (TypeParseTreeException $e) {
                            $storage->docblock_issues[] = new InvalidDocblock(
                                $e->getMessage() . ' in docblock for ' . implode('.', $this->fq_classlike_names),
                                $name_location ?: $class_location
                            );
                        }
                    }

                    $storage->sealed_properties = true;
                }

                foreach ($docblock_info->methods as $method) {
                    /** @var MethodStorage */
                    $pseudo_method_storage = $this->registerFunctionLike($method, true);

                    if ($pseudo_method_storage->is_static) {
                        $storage->pseudo_static_methods[strtolower($method->name->name)] = $pseudo_method_storage;
                    } else {
                        $storage->pseudo_methods[strtolower($method->name->name)] = $pseudo_method_storage;
                    }

                    $storage->sealed_methods = true;
                }

                $storage->deprecated = $docblock_info->deprecated;
                $storage->internal = $docblock_info->internal;
                $storage->psalm_internal = $docblock_info->psalm_internal;

                if ($docblock_info->mixin) {
                    $mixin_type = Type::parseTokens(
                        Type::fixUpLocalType(
                            $docblock_info->mixin,
                            $this->aliases,
                            $this->class_template_types,
                            $this->type_aliases,
                            $fq_classlike_name
                        ),
                        null,
                        $this->class_template_types
                    );

                    $mixin_type->queueClassLikesForScanning(
                        $this->codebase,
                        $this->file_storage,
                        $storage->template_types ?: []
                    );

                    if ($mixin_type->isSingle()) {
                        $mixin_type = \array_values($mixin_type->getAtomicTypes())[0];

                        if ($mixin_type instanceof Type\Atomic\TNamedObject
                            || $mixin_type instanceof Type\Atomic\TTemplateParam
                        ) {
                            $storage->mixin = $mixin_type;
                        }
                    }
                }

                $storage->mutation_free = $docblock_info->mutation_free;
                $storage->external_mutation_free = $docblock_info->external_mutation_free;

                $storage->override_property_visibility = $docblock_info->override_property_visibility;
                $storage->override_method_visibility = $docblock_info->override_method_visibility;

                $storage->suppressed_issues = $docblock_info->suppressed_issues;
            }
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
    }

    /**
     * @return void
     */
    private function extendTemplatedType(
        ClassLikeStorage $storage,
        PhpParser\Node\Stmt\ClassLike $node,
        string $extended_class_name
    ) {
        if (trim($extended_class_name) === '') {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Extended class cannot be empty in docblock for ' . implode('.', $this->fq_classlike_names),
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        try {
            $extended_union_type = Type::parseTokens(
                Type::fixUpLocalType(
                    $extended_class_name,
                    $this->aliases,
                    $this->class_template_types,
                    $this->type_aliases
                ),
                null,
                $this->class_template_types
            );
        } catch (TypeParseTreeException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . implode('.', $this->fq_classlike_names),
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        if (!$extended_union_type->isSingle()) {
            $storage->docblock_issues[] = new InvalidDocblock(
                '@template-extends cannot be a union type',
                new CodeLocation($this->file_scanner, $node, null, true)
            );
        }

        $extended_union_type->setFromDocblock();

        $extended_union_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $storage->template_types ?: []
        );

        foreach ($extended_union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof Type\Atomic\TGenericObject) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-extends has invalid class ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true)
                );

                return;
            }

            $generic_class_lc = strtolower($atomic_type->value);

            if (!isset($storage->parent_classes[$generic_class_lc])
                && !isset($storage->parent_interfaces[$generic_class_lc])
            ) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-extends must include the name of an extended class,'
                        . ' got ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true)
                );
            }

            $extended_type_parameters = [];

            $storage->template_type_extends_count = count($atomic_type->type_params);

            foreach ($atomic_type->type_params as $type_param) {
                $extended_type_parameters[] = $type_param;
            }

            $storage->template_type_extends[$atomic_type->value] = $extended_type_parameters;
        }
    }

    /**
     * @return void
     */
    private function implementTemplatedType(
        ClassLikeStorage $storage,
        PhpParser\Node\Stmt\ClassLike $node,
        string $implemented_class_name
    ) {
        if (trim($implemented_class_name) === '') {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Extended class cannot be empty in docblock for ' . implode('.', $this->fq_classlike_names),
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        try {
            $implemented_union_type = Type::parseTokens(
                Type::fixUpLocalType(
                    $implemented_class_name,
                    $this->aliases,
                    $this->class_template_types,
                    $this->type_aliases
                ),
                null,
                $this->class_template_types
            );
        } catch (TypeParseTreeException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . implode('.', $this->fq_classlike_names),
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        if (!$implemented_union_type->isSingle()) {
            $storage->docblock_issues[] = new InvalidDocblock(
                '@template-implements cannot be a union type',
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        $implemented_union_type->setFromDocblock();

        $implemented_union_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $storage->template_types ?: []
        );

        foreach ($implemented_union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof Type\Atomic\TGenericObject) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-implements has invalid class ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true)
                );

                return;
            }

            $generic_class_lc = strtolower($atomic_type->value);

            if (!isset($storage->class_implements[$generic_class_lc])) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-implements must include the name of an implemented class,'
                        . ' got ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true)
                );

                return;
            }

            $implemented_type_parameters = [];

            $storage->template_type_implements_count[$generic_class_lc] = count($atomic_type->type_params);

            foreach ($atomic_type->type_params as $type_param) {
                $implemented_type_parameters[] = $type_param;
            }

            $storage->template_type_extends[$atomic_type->value] = $implemented_type_parameters;
        }
    }

    /**
     * @return void
     */
    private function useTemplatedType(
        ClassLikeStorage $storage,
        PhpParser\Node\Stmt\TraitUse $node,
        string $used_class_name
    ) {
        if (trim($used_class_name) === '') {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Extended class cannot be empty in docblock for ' . implode('.', $this->fq_classlike_names),
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        try {
            $used_union_type = Type::parseTokens(
                Type::fixUpLocalType(
                    $used_class_name,
                    $this->aliases,
                    $this->class_template_types,
                    $this->type_aliases
                ),
                null,
                $this->class_template_types
            );
        } catch (TypeParseTreeException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . implode('.', $this->fq_classlike_names),
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        if (!$used_union_type->isSingle()) {
            $storage->docblock_issues[] = new InvalidDocblock(
                '@template-use cannot be a union type',
                new CodeLocation($this->file_scanner, $node, null, true)
            );

            return;
        }

        $used_union_type->setFromDocblock();

        $used_union_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $storage->template_types ?: []
        );

        foreach ($used_union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof Type\Atomic\TGenericObject) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-use has invalid class ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true)
                );

                return;
            }

            $generic_class_lc = strtolower($atomic_type->value);

            if (!isset($storage->used_traits[$generic_class_lc])) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    '@template-use must include the name of an used class,'
                        . ' got ' . $atomic_type->getId(),
                    new CodeLocation($this->file_scanner, $node, null, true)
                );

                return;
            }

            $used_type_parameters = [];

            $storage->template_type_uses_count[$generic_class_lc] = count($atomic_type->type_params);

            foreach ($atomic_type->type_params as $type_param) {
                $used_type_parameters[] = $type_param;
            }

            $storage->template_type_extends[$atomic_type->value] = $used_type_parameters;
        }
    }

    /**
     * @param  PhpParser\Node\FunctionLike $stmt
     * @param  bool $fake_method in the case of @method annotations we do something a little strange
     *
     * @return FunctionLikeStorage|false
     */
    private function registerFunctionLike(PhpParser\Node\FunctionLike $stmt, $fake_method = false)
    {
        $class_storage = null;
        $fq_classlike_name = null;

        if ($fake_method && $stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            $cased_function_id = '@method ' . $stmt->name->name;

            $storage = new MethodStorage();
            $storage->defining_fqcln = '';
            $storage->is_static = $stmt->isStatic();
            $class_storage = $this->classlike_storages[count($this->classlike_storages) - 1];
            $storage->final = $class_storage->final;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $cased_function_id =
                ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $stmt->name->name;
            $function_id = strtolower($cased_function_id);

            $storage = new FunctionLikeStorage();

            if ($this->codebase->register_stub_files || $this->codebase->register_autoload_files) {
                if (isset($this->file_storage->functions[$function_id])
                    && ($this->codebase->register_stub_files
                        || !$this->codebase->functions->hasStubbedFunction($function_id))
                ) {
                    $this->codebase->functions->addGlobalFunction(
                        $function_id,
                        $this->file_storage->functions[$function_id]
                    );

                    $storage = $this->file_storage->functions[$function_id];
                    $this->functionlike_storages[] = $storage;

                    return $storage;
                }
            } else {
                if (isset($this->file_storage->functions[$function_id])) {
                    $duplicate_function_storage = $this->file_storage->functions[$function_id];

                    if ($duplicate_function_storage->location
                        && $duplicate_function_storage->location->getLineNumber() === $stmt->getLine()
                    ) {
                        $storage = $this->file_storage->functions[$function_id];
                        $this->functionlike_storages[] = $storage;

                        return $storage;
                    }

                    if (IssueBuffer::accepts(
                        new DuplicateFunction(
                            'Method ' . $function_id . ' has already been defined'
                                . ($duplicate_function_storage->location
                                    ? ' in ' . $duplicate_function_storage->location->file_path
                                    : ''),
                            new CodeLocation($this->file_scanner, $stmt, null, true)
                        )
                    )) {
                        // fall through
                    }

                    $this->file_storage->has_visitor_issues = true;

                    $duplicate_function_storage->has_visitor_issues = true;

                    $storage = $this->file_storage->functions[$function_id];
                    $this->functionlike_storages[] = $storage;

                    return $storage;
                }

                if (isset($this->config->getPredefinedFunctions()[$function_id])) {
                    /** @psalm-suppress TypeCoercion */
                    $reflection_function = new \ReflectionFunction($function_id);

                    if ($reflection_function->getFileName() !== $this->file_path) {
                        if (IssueBuffer::accepts(
                            new DuplicateFunction(
                                'Method ' . $function_id . ' has already been defined as a core function',
                                new CodeLocation($this->file_scanner, $stmt, null, true)
                            )
                        )) {
                            // fall through
                        }
                    }
                }
            }

            if ($this->codebase->register_stub_files
                || ($this->codebase->register_autoload_files
                    && !$this->codebase->functions->hasStubbedFunction($function_id))
            ) {
                $this->codebase->functions->addGlobalFunction($function_id, $storage);
            }

            $this->file_storage->functions[$function_id] = $storage;
            $this->file_storage->declaring_function_ids[$function_id] = strtolower($this->file_path);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            if (!$this->fq_classlike_names) {
                throw new \LogicException('$this->fq_classlike_names should not be null');
            }

            $fq_classlike_name = $this->fq_classlike_names[count($this->fq_classlike_names) - 1];

            $method_name_lc = strtolower($stmt->name->name);

            $function_id = $fq_classlike_name . '::' . $method_name_lc;
            $cased_function_id = $fq_classlike_name . '::' . $stmt->name->name;

            if (!$this->classlike_storages) {
                throw new \UnexpectedValueException('$class_storages cannot be empty for ' . $function_id);
            }

            $class_storage = $this->classlike_storages[count($this->classlike_storages) - 1];

            $storage = null;

            if (isset($class_storage->methods[$method_name_lc])) {
                if (!$this->codebase->register_stub_files) {
                    $duplicate_method_storage = $class_storage->methods[$method_name_lc];

                    if (IssueBuffer::accepts(
                        new DuplicateMethod(
                            'Method ' . $function_id . ' has already been defined'
                                . ($duplicate_method_storage->location
                                    ? ' in ' . $duplicate_method_storage->location->file_path
                                    : ''),
                            new CodeLocation($this->file_scanner, $stmt, null, true)
                        )
                    )) {
                        // fall through
                    }

                    $this->file_storage->has_visitor_issues = true;

                    $duplicate_method_storage->has_visitor_issues = true;

                    return false;
                }

                $storage = $class_storage->methods[$method_name_lc];
            }

            if (!$storage) {
                $storage = $class_storage->methods[$method_name_lc] = new MethodStorage();
            }

            $storage->defining_fqcln = $fq_classlike_name;

            $class_name_parts = explode('\\', $fq_classlike_name);
            $class_name = array_pop($class_name_parts);

            if ($method_name_lc === strtolower($class_name) &&
                !isset($class_storage->methods['__construct']) &&
                strpos($fq_classlike_name, '\\') === false
            ) {
                $this->codebase->methods->setDeclaringMethodId(
                    $fq_classlike_name,
                    '__construct',
                    $fq_classlike_name,
                    $method_name_lc
                );

                $this->codebase->methods->setAppearingMethodId(
                    $fq_classlike_name,
                    '__construct',
                    $fq_classlike_name,
                    $method_name_lc
                );
            }

            $method_id = new \Psalm\Internal\MethodIdentifier(
                $fq_classlike_name,
                $method_name_lc
            );

            $class_storage->declaring_method_ids[$method_name_lc]
                = $class_storage->appearing_method_ids[$method_name_lc]
                = $method_id;

            if (!$stmt->isPrivate() || $method_name_lc === '__construct' || $class_storage->is_trait) {
                $class_storage->inheritable_method_ids[$method_name_lc] = $method_id;
            }

            if (!isset($class_storage->overridden_method_ids[$method_name_lc])) {
                $class_storage->overridden_method_ids[$method_name_lc] = [];
            }

            $storage->is_static = $stmt->isStatic();
            $storage->abstract = $stmt->isAbstract();

            $storage->final = $class_storage->final || $stmt->isFinal();

            if ($stmt->isPrivate()) {
                $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
            } elseif ($stmt->isProtected()) {
                $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            } else {
                $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
            }
        } else {
            $function_id = $cased_function_id = strtolower($this->file_path)
                . ':' . $stmt->getLine()
                . ':' . (int) $stmt->getAttribute('startFilePos') . ':-:closure';

            $storage = $this->file_storage->functions[$function_id] = new FunctionLikeStorage();
        }

        $this->functionlike_storages[] = $storage;

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
            $storage->cased_name = $stmt->name->name;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
            $storage->cased_name =
                ($this->aliases->namespace ? $this->aliases->namespace . '\\' : '') . $stmt->name->name;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod || $stmt instanceof PhpParser\Node\Stmt\Function_) {
            $storage->location = new CodeLocation($this->file_scanner, $stmt->name, null, true);
        } else {
            $storage->location = new CodeLocation($this->file_scanner, $stmt, null, true);
        }

        $storage->stmt_location = new CodeLocation($this->file_scanner, $stmt);

        $required_param_count = 0;
        $i = 0;
        $has_optional_param = false;

        $existing_params = [];
        $storage->params = [];

        foreach ($stmt->getParams() as $param) {
            if ($param->var instanceof PhpParser\Node\Expr\Error) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    'Param' . ($i + 1) . ' of ' . $cased_function_id . ' has invalid syntax',
                    new CodeLocation($this->file_scanner, $param, null, true)
                );

                ++$i;

                continue;
            }

            $param_array = $this->getTranslatedFunctionParam($param, $stmt, $fake_method, $fq_classlike_name);

            if (isset($existing_params['$' . $param_array->name])) {
                $storage->docblock_issues[] = new DuplicateParam(
                    'Duplicate param $' . $param_array->name . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $param, null, true)
                );

                ++$i;

                continue;
            }

            $existing_params['$' . $param_array->name] = $i;
            $storage->param_types[$param_array->name] = $param_array->type;
            $storage->params[] = $param_array;

            if (!$param_array->is_optional && !$param_array->is_variadic) {
                $required_param_count = $i + 1;

                if (!$param->variadic
                    && $has_optional_param
                ) {
                    foreach ($storage->params as $param) {
                        $param->is_optional = false;
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
            && $stmt->stmts
        ) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
                && $storage instanceof MethodStorage
                && $class_storage
                && !$class_storage->mutation_free
                && count($stmt->stmts) === 1
                && !count($stmt->params)
                && $stmt->stmts[0] instanceof PhpParser\Node\Stmt\Return_
                && $stmt->stmts[0]->expr instanceof PhpParser\Node\Expr\PropertyFetch
                && $stmt->stmts[0]->expr->var instanceof PhpParser\Node\Expr\Variable
                && $stmt->stmts[0]->expr->var->name === 'this'
            ) {
                $storage->mutation_free = true;
                $storage->external_mutation_free = true;
                $storage->mutation_free_inferred = true;

                if ($stmt->stmts[0]->expr->name instanceof PhpParser\Node\Identifier) {
                    $property_name = $stmt->stmts[0]->expr->name->name;

                    if (isset($class_storage->properties[$property_name])
                        && $class_storage->properties[$property_name]->type
                        && ($class_storage->properties[$property_name]->type->isNullable()
                            || $class_storage->properties[$property_name]->type->isFalsable()
                            || $class_storage->properties[$property_name]->type->hasArray()
                        )
                    ) {
                        $storage->plain_getter = $property_name;

                        $storage->if_true_assertions[] = new \Psalm\Storage\Assertion(
                            '$this->' . $property_name,
                            [['!falsy']]
                        );
                    }
                }
            } elseif (strpos($stmt->name->name, 'assert') === 0) {
                $var_assertions = [];

                foreach ($stmt->stmts as $function_stmt) {
                    if ($function_stmt instanceof PhpParser\Node\Stmt\If_) {
                        $final_actions = \Psalm\Internal\Analyzer\ScopeAnalyzer::getFinalControlActions(
                            $function_stmt->stmts,
                            null,
                            $this->config->exit_functions,
                            [],
                            false
                        );

                        if ($final_actions !== [\Psalm\Internal\Analyzer\ScopeAnalyzer::ACTION_END]) {
                            $var_assertions = [];
                            break;
                        }

                        $if_clauses = \Psalm\Type\Algebra::getFormula(
                            \spl_object_id($function_stmt->cond),
                            $function_stmt->cond,
                            $this->fq_classlike_names
                                ? $this->fq_classlike_names[count($this->fq_classlike_names) - 1]
                                : null,
                            $this->file_scanner,
                            null
                        );

                        $negated_formula = \Psalm\Type\Algebra::negateFormula($if_clauses);

                        $rules = \Psalm\Type\Algebra::getTruthsFromFormula($negated_formula);

                        if (!$rules) {
                            $var_assertions = [];
                            break;
                        }

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
                    } else {
                        $var_assertions = [];
                        break;
                    }
                }

                $storage->assertions = $var_assertions;
            }
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
                    && $function_stmt->expr->expr instanceof PhpParser\Node\Expr\FuncCall
                    && $function_stmt->expr->expr->name instanceof PhpParser\Node\Name
                ) {
                    $function_id = implode('\\', $function_stmt->expr->expr->name->parts);

                    if ($function_id === 'func_get_arg'
                        || $function_id === 'func_get_args'
                        || $function_id === 'func_num_args'
                    ) {
                        $storage->variadic = true;
                    }
                } elseif ($function_stmt instanceof PhpParser\Node\Stmt\If_
                    && $function_stmt->cond instanceof PhpParser\Node\Expr\BinaryOp
                    && $function_stmt->cond->left instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    && $function_stmt->cond->left->left instanceof PhpParser\Node\Expr\FuncCall
                    && $function_stmt->cond->left->left->name instanceof PhpParser\Node\Name
                ) {
                    $function_id = implode('\\', $function_stmt->cond->left->left->name->parts);

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

            $original_type = $parser_return_type;

            if ($parser_return_type instanceof PhpParser\Node\NullableType) {
                $suffix = '|null';
                $parser_return_type = $parser_return_type->type;
            }

            if ($parser_return_type instanceof PhpParser\Node\Identifier) {
                $return_type_string = $parser_return_type->name . $suffix;
            } elseif ($parser_return_type instanceof PhpParser\Node\UnionType) {
                // for now unsupported
                $return_type_string = 'mixed';
            } else {
                $return_type_fq_classlike_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $parser_return_type,
                    $this->aliases
                );

                if ($class_storage && !$class_storage->is_trait && $return_type_fq_classlike_name === 'self') {
                    $return_type_fq_classlike_name = $class_storage->name;
                }

                $return_type_string = $return_type_fq_classlike_name . $suffix;
            }

            $storage->return_type = Type::parseString(
                $return_type_string,
                [$this->php_major_version, $this->php_minor_version]
            );
            $storage->return_type->queueClassLikesForScanning($this->codebase, $this->file_storage);

            $storage->return_type_location = new CodeLocation(
                $this->file_scanner,
                $original_type
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
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
                && $stmt->name->name === '__construct'
                && $class_storage
                && $storage instanceof MethodStorage
                && $storage->params
                && $this->config->infer_property_types_from_constructor
            ) {
                $this->inferPropertyTypeFromConstructor($stmt, $storage, $class_storage);
            }

            return $storage;
        }

        try {
            $docblock_info = CommentAnalyzer::extractFunctionDocblockInfo($doc_comment);
        } catch (IncorrectDocblockException $e) {
            $storage->docblock_issues[] = new MissingDocblockType(
                $e->getMessage() . ' in docblock for ' . $cased_function_id,
                new CodeLocation($this->file_scanner, $stmt, null, true)
            );

            $docblock_info = null;
        } catch (DocblockParseException $e) {
            $storage->docblock_issues[] = new InvalidDocblock(
                $e->getMessage() . ' in docblock for ' . $cased_function_id,
                new CodeLocation($this->file_scanner, $stmt, null, true)
            );

            $docblock_info = null;
        }

        if (!$docblock_info) {
            return $storage;
        }

        if ($docblock_info->mutation_free) {
            $storage->mutation_free = true;

            if ($storage instanceof MethodStorage) {
                $storage->external_mutation_free = true;
                $storage->mutation_free_inferred = false;
            }
        }

        if ($storage instanceof MethodStorage && $docblock_info->external_mutation_free) {
            $storage->external_mutation_free = true;
        }

        if ($docblock_info->deprecated) {
            $storage->deprecated = true;
        }

        if ($docblock_info->internal) {
            $storage->internal = true;
        }

        if ($docblock_info->psalm_internal) {
            $storage->psalm_internal = $docblock_info->psalm_internal;
        }

        if ($docblock_info->variadic) {
            $storage->variadic = true;
        }

        if ($docblock_info->pure) {
            $storage->pure = true;
            $storage->mutation_free = true;
            if ($storage instanceof MethodStorage) {
                $storage->external_mutation_free = true;
            }
        }

        if ($docblock_info->remove_taint) {
            $storage->remove_taint = true;
        }

        if ($docblock_info->ignore_nullable_return && $storage->return_type) {
            $storage->return_type->ignore_nullable_issues = true;
        }

        if ($docblock_info->ignore_falsable_return && $storage->return_type) {
            $storage->return_type->ignore_falsable_issues = true;
        }

        $storage->suppressed_issues = $docblock_info->suppressed_issues;

        foreach ($docblock_info->throws as [$throw, $offset, $line]) {
            $throw_location = new CodeLocation\DocblockTypeLocation(
                $this->file_scanner,
                $offset,
                $offset + \strlen($throw),
                $line
            );

            foreach (\array_map('trim', explode('|', $throw)) as $throw_class) {
                if ($throw_class !== 'self' && $throw_class !== 'static' && $throw_class !== 'parent') {
                    $exception_fqcln = Type::getFQCLNFromString(
                        $throw_class,
                        $this->aliases
                    );
                } else {
                    $exception_fqcln = $throw_class;
                }

                $this->codebase->scanner->queueClassLikeForScanning($exception_fqcln);
                $this->file_storage->referenced_classlikes[strtolower($exception_fqcln)] = $exception_fqcln;
                $storage->throws[$exception_fqcln] = true;
                $storage->throw_locations[$exception_fqcln] = $throw_location;
            }
        }

        if (!$this->config->use_docblock_types) {
            return $storage;
        }

        if ($storage instanceof MethodStorage && $docblock_info->inheritdoc) {
            $storage->inheritdoc = true;
        }

        $template_types = $class_storage && $class_storage->template_types ? $class_storage->template_types : null;

        if ($docblock_info->templates) {
            $storage->template_types = [];

            foreach ($docblock_info->templates as $i => $template_map) {
                $template_name = $template_map[0];

                if ($template_map[1] !== null && $template_map[2] !== null) {
                    if (trim($template_map[2])) {
                        try {
                            $template_type = Type::parseTokens(
                                Type::fixUpLocalType(
                                    $template_map[2],
                                    $this->aliases,
                                    $storage->template_types + ($template_types ?: []),
                                    $this->type_aliases
                                ),
                                null,
                                $storage->template_types + ($template_types ?: [])
                            );
                        } catch (TypeParseTreeException $e) {
                            $storage->docblock_issues[] = new InvalidDocblock(
                                'Template ' . $template_name . ' has invalid as type - ' . $e->getMessage(),
                                new CodeLocation($this->file_scanner, $stmt, null, true)
                            );

                            $template_type = Type::getMixed();
                        }
                    } else {
                        $storage->docblock_issues[] = new InvalidDocblock(
                            'Template ' . $template_name . ' missing as type',
                            new CodeLocation($this->file_scanner, $stmt, null, true)
                        );

                        $template_type = Type::getMixed();
                    }
                } else {
                    $template_type = Type::getMixed();
                }

                if (isset($template_types[$template_name])) {
                    $storage->docblock_issues[] = new InvalidDocblock(
                        'Duplicate template param ' . $template_name . ' in docblock for '
                            . $cased_function_id,
                        new CodeLocation($this->file_scanner, $stmt, null, true)
                    );
                } else {
                    $storage->template_types[$template_name] = [
                        'fn-' . strtolower($cased_function_id) => [$template_type],
                    ];
                }

                $storage->template_covariants[$i] = $template_map[3];
            }

            $template_types = array_merge($template_types ?: [], $storage->template_types);

            $this->function_template_types = $template_types;
        }

        if ($docblock_info->assertions) {
            $storage->assertions = [];

            foreach ($docblock_info->assertions as $assertion) {
                $assertion_type_parts = $this->getAssertionParts(
                    $storage,
                    $assertion['type'],
                    $stmt
                );

                if (!$assertion_type_parts) {
                    continue;
                }

                foreach ($storage->params as $i => $param) {
                    if ($param->name === $assertion['param_name']) {
                        $storage->assertions[] = new \Psalm\Storage\Assertion(
                            $i,
                            [$assertion_type_parts]
                        );
                        continue 2;
                    }
                }

                $storage->assertions[] = new \Psalm\Storage\Assertion(
                    '$' . $assertion['param_name'],
                    [$assertion_type_parts]
                );
            }
        }

        if ($docblock_info->if_true_assertions) {
            $storage->if_true_assertions = [];

            foreach ($docblock_info->if_true_assertions as $assertion) {
                $assertion_type_parts = $this->getAssertionParts(
                    $storage,
                    $assertion['type'],
                    $stmt
                );

                if (!$assertion_type_parts) {
                    continue;
                }

                foreach ($storage->params as $i => $param) {
                    if ($param->name === $assertion['param_name']) {
                        $storage->if_true_assertions[] = new \Psalm\Storage\Assertion(
                            $i,
                            [$assertion_type_parts]
                        );
                        continue 2;
                    }
                }

                $storage->if_true_assertions[] = new \Psalm\Storage\Assertion(
                    '$' . $assertion['param_name'],
                    [$assertion_type_parts]
                );
            }
        }

        if ($docblock_info->if_false_assertions) {
            $storage->if_false_assertions = [];

            foreach ($docblock_info->if_false_assertions as $assertion) {
                $assertion_type_parts = $this->getAssertionParts(
                    $storage,
                    $assertion['type'],
                    $stmt
                );

                if (!$assertion_type_parts) {
                    continue;
                }

                foreach ($storage->params as $i => $param) {
                    if ($param->name === $assertion['param_name']) {
                        $storage->if_false_assertions[] = new \Psalm\Storage\Assertion(
                            $i,
                            [$assertion_type_parts]
                        );
                        continue 2;
                    }
                }

                $storage->if_false_assertions[] = new \Psalm\Storage\Assertion(
                    '$' . $assertion['param_name'],
                    [$assertion_type_parts]
                );
            }
        }

        foreach ($docblock_info->globals as $global) {
            try {
                $storage->global_types[$global['name']] = Type::parseTokens(
                    Type::fixUpLocalType(
                        $global['type'],
                        $this->aliases,
                        null,
                        $this->type_aliases
                    ),
                    null
                );
            } catch (TypeParseTreeException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );

                continue;
            }
        }

        if ($docblock_info->params) {
            $this->improveParamsFromDocblock(
                $storage,
                $docblock_info->params,
                $stmt,
                $fake_method,
                $class_storage && !$class_storage->is_trait ? $class_storage->name : null
            );
        }

        if ($storage instanceof MethodStorage) {
            $storage->has_docblock_param_types = (bool) array_filter(
                $storage->params,
                /** @return bool */
                function (FunctionLikeParameter $p) {
                    return $p->type !== null && $p->has_docblock_type;
                }
            );
        }

        $class_template_types = $this->class_template_types;

        foreach ($docblock_info->params_out as $docblock_param_out) {
            $param_name = substr($docblock_param_out['name'], 1);

            try {
                $out_type = Type::parseTokens(
                    Type::fixUpLocalType(
                        $docblock_param_out['type'],
                        $this->aliases,
                        $this->function_template_types + $class_template_types,
                        $this->type_aliases
                    ),
                    null,
                    $this->function_template_types + $class_template_types
                );
            } catch (TypeParseTreeException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );

                continue;
            }

            $out_type->queueClassLikesForScanning(
                $this->codebase,
                $this->file_storage,
                $storage->template_types ?: []
            );

            foreach ($storage->params as $i => $param_storage) {
                if ($param_storage->name === $param_name) {
                    $storage->param_out_types[$i] = $out_type;
                }
            }
        }

        foreach ($docblock_info->taint_sink_params as $taint_sink_param) {
            $param_name = substr($taint_sink_param['name'], 1);

            foreach ($storage->params as $param_storage) {
                if ($param_storage->name === $param_name) {
                    $param_storage->sink = (int) Type\Union::TAINTED_INPUT;
                }
            }
        }

        foreach ($docblock_info->assert_untainted_params as $untainted_assert_param) {
            $param_name = substr($untainted_assert_param['name'], 1);

            foreach ($storage->params as $param_storage) {
                if ($param_storage->name === $param_name) {
                    $param_storage->assert_untainted = true;
                }
            }
        }

        if ($docblock_info->template_typeofs) {
            foreach ($docblock_info->template_typeofs as $template_typeof) {
                foreach ($storage->params as $param) {
                    if ($param->name === $template_typeof['param_name']) {
                        $param_type_nullable = $param->type && $param->type->isNullable();

                        $template_type = null;
                        $template_class = null;

                        if (isset($template_types[$template_typeof['template_type']])) {
                            foreach ($template_types[$template_typeof['template_type']] as $class => $map) {
                                $template_type = $map[0];
                                $template_class = $class;
                            }
                        }

                        $template_atomic_type = null;

                        if ($template_type) {
                            foreach ($template_type->getAtomicTypes() as $tat) {
                                if ($tat instanceof Type\Atomic\TNamedObject) {
                                    $template_atomic_type = $tat;
                                }
                            }
                        }

                        $param->type = new Type\Union([
                            new Type\Atomic\TTemplateParamClass(
                                $template_typeof['template_type'],
                                $template_type && !$template_type->isMixed()
                                    ? (string)$template_type
                                    : 'object',
                                $template_atomic_type,
                                $template_class ?: 'fn-' . strtolower($cased_function_id)
                            ),
                        ]);

                        if ($param_type_nullable) {
                            $param->type->addType(new Type\Atomic\TNull);
                        }

                        break;
                    }
                }
            }
        }

        if ($docblock_info->return_type) {
            $docblock_return_type = $docblock_info->return_type;

            if (!$fake_method
                && $docblock_info->return_type_line_number
                && $docblock_info->return_type_start
                && $docblock_info->return_type_end
            ) {
                $storage->return_type_location = new CodeLocation\DocblockTypeLocation(
                    $this->file_scanner,
                    $docblock_info->return_type_start,
                    $docblock_info->return_type_end,
                    $docblock_info->return_type_line_number
                );
            } else {
                $storage->return_type_location = new CodeLocation(
                    $this->file_scanner,
                    $stmt,
                    null,
                    false,
                    !$fake_method
                        ? CodeLocation::FUNCTION_PHPDOC_RETURN_TYPE
                        : CodeLocation::FUNCTION_PHPDOC_METHOD,
                    $docblock_info->return_type
                );
            }

            try {
                $fixed_type_tokens = Type::fixUpLocalType(
                    $docblock_return_type,
                    $this->aliases,
                    $this->function_template_types + $class_template_types,
                    $this->type_aliases,
                    $class_storage && !$class_storage->is_trait ? $class_storage->name : null
                );

                $param_type_mapping = [];

                // This checks for param references in the return type tokens
                // If found, the param is replaced with a generated template param
                foreach ($fixed_type_tokens as $i => $type_token) {
                    $token_body = $type_token[0];
                    $template_function_id = 'fn-' . strtolower($cased_function_id);

                    if ($token_body[0] === '$') {
                        foreach ($storage->params as $j => $param_storage) {
                            if ('$' . $param_storage->name === $token_body) {
                                if (!isset($param_type_mapping[$token_body])) {
                                    $template_name = 'TGeneratedFromParam' . $j;

                                    $template_as_type = $param_storage->type
                                        ? clone $param_storage->type
                                        : Type::getMixed();

                                    $storage->template_types[$template_name] = [
                                        $template_function_id => [
                                            $template_as_type
                                        ],
                                    ];

                                    $this->function_template_types[$template_name]
                                        = $storage->template_types[$template_name];

                                    $param_type_mapping[$token_body] = $template_name;

                                    $param_storage->type = new Type\Union([
                                        new Type\Atomic\TTemplateParam(
                                            $template_name,
                                            $template_as_type,
                                            $template_function_id
                                        )
                                    ]);
                                }

                                // spaces are allowed before $foo in get(string $foo) magic method
                                // definitions, but we want to remove them in this instance
                                if (isset($fixed_type_tokens[$i - 1])
                                    && $fixed_type_tokens[$i - 1][0][0] === ' '
                                ) {
                                    unset($fixed_type_tokens[$i - 1]);
                                }

                                $fixed_type_tokens[$i][0] = $param_type_mapping[$token_body];

                                continue 2;
                            }
                        }
                    }

                    if ($token_body === 'func_num_args()') {
                        $template_name = 'TFunctionArgCount';

                        $storage->template_types[$template_name] = [
                            $template_function_id => [
                                Type::getInt()
                            ],
                        ];

                        $this->function_template_types[$template_name]
                            = $storage->template_types[$template_name];

                        $fixed_type_tokens[$i][0] = $template_name;
                    }
                }

                $storage->return_type = Type::parseTokens(
                    \array_values($fixed_type_tokens),
                    null,
                    $this->function_template_types + $class_template_types
                );

                $storage->return_type->setFromDocblock();

                if ($storage->signature_return_type) {
                    $all_typehint_types_match = true;
                    $signature_return_atomic_types = $storage->signature_return_type->getAtomicTypes();

                    foreach ($storage->return_type->getAtomicTypes() as $key => $type) {
                        if (isset($signature_return_atomic_types[$key])) {
                            $type->from_docblock = false;
                        } else {
                            $all_typehint_types_match = false;
                        }
                    }

                    if ($all_typehint_types_match) {
                        $storage->return_type->from_docblock = false;
                    }

                    if ($storage->signature_return_type->isNullable()
                        && !$storage->return_type->isNullable()
                    ) {
                        $storage->return_type->addType(new Type\Atomic\TNull());
                    }
                }

                $storage->return_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
            } catch (TypeParseTreeException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );
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

            if ($docblock_info->return_type_line_number && !$fake_method) {
                $storage->return_type_location->setCommentLine($docblock_info->return_type_line_number);
            }

            $storage->return_type_description = $docblock_info->return_type_description;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod
            && $stmt->name->name === '__construct'
            && $class_storage
            && $storage instanceof MethodStorage
            && $storage->params
            && $this->config->infer_property_types_from_constructor
        ) {
            $this->inferPropertyTypeFromConstructor($stmt, $storage, $class_storage);
        }

        return $storage;
    }

    private function inferPropertyTypeFromConstructor(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        MethodStorage $storage,
        ClassLikeStorage $class_storage
    ) : void {
        if (!$stmt->stmts) {
            return;
        }

        $assigned_properties = [];

        foreach ($stmt->stmts as $function_stmt) {
            if ($function_stmt instanceof PhpParser\Node\Stmt\Expression
                && $function_stmt->expr instanceof PhpParser\Node\Expr\Assign
                && $function_stmt->expr->var instanceof PhpParser\Node\Expr\PropertyFetch
                && $function_stmt->expr->var->var instanceof PhpParser\Node\Expr\Variable
                && $function_stmt->expr->var->var->name === 'this'
                && $function_stmt->expr->var->name instanceof PhpParser\Node\Identifier
                && ($property_name = $function_stmt->expr->var->name->name)
                && isset($class_storage->properties[$property_name])
                && $function_stmt->expr->expr instanceof PhpParser\Node\Expr\Variable
                && is_string($function_stmt->expr->expr->name)
                && ($param_name = $function_stmt->expr->expr->name)
                && array_key_exists($param_name, $storage->param_types)
            ) {
                if ($class_storage->properties[$property_name]->type
                    || !isset($storage->param_types[$param_name])
                ) {
                    continue;
                }

                $param_index = \array_search($param_name, \array_keys($storage->param_types), true);

                if ($param_index === false || !isset($storage->params[$param_index]->type)) {
                    continue;
                }

                $param_type = $storage->params[$param_index]->type;

                $assigned_properties[$property_name] =
                    $storage->params[$param_index]->is_variadic
                        ? new Type\Union([
                            new Type\Atomic\TArray([
                                Type::getInt(),
                                $param_type,
                            ]),
                        ])
                        : $param_type;
            } else {
                $assigned_properties = [];
                break;
            }
        }

        if (!$assigned_properties) {
            return;
        }

        $storage->external_mutation_free = true;

        foreach ($assigned_properties as $property_name => $property_type) {
            $class_storage->properties[$property_name]->type = clone $property_type;
        }
    }

    /**
     * @return ?list<string>
     */
    private function getAssertionParts(
        FunctionLikeStorage $storage,
        string $assertion_type,
        PhpParser\Node\FunctionLike $stmt
    ) : ?array {
        $prefix = '';

        if ($assertion_type[0] === '!') {
            $prefix = '!';
            $assertion_type = substr($assertion_type, 1);
        }

        if ($assertion_type[0] === '~') {
            $prefix .= '~';
            $assertion_type = substr($assertion_type, 1);
        }

        if ($assertion_type[0] === '=') {
            $prefix .= '=';
            $assertion_type = substr($assertion_type, 1);
        }

        $class_template_types = !$stmt instanceof PhpParser\Node\Stmt\ClassMethod || !$stmt->isStatic()
            ? $this->class_template_types
            : [];

        $namespaced_type = Type::parseTokens(
            Type::fixUpLocalType(
                $assertion_type,
                $this->aliases,
                $this->function_template_types + $class_template_types,
                $this->type_aliases,
                null,
                null,
                true
            )
        );

        if ($prefix && count($namespaced_type->getAtomicTypes()) > 1) {
            $storage->docblock_issues[] = new InvalidDocblock(
                'Docblock assertions cannot contain | characters together with ' . $prefix,
                new CodeLocation($this->file_scanner, $stmt, null, true)
            );

            return null;
        }

        $namespaced_type->queueClassLikesForScanning(
            $this->codebase,
            $this->file_storage,
            $this->function_template_types + $class_template_types
        );

        $assertion_type_parts = [];

        foreach ($namespaced_type->getAtomicTypes() as $namespaced_type_part) {
            if ($namespaced_type_part instanceof Type\Atomic\TAssertionFalsy
                || ($namespaced_type_part instanceof Type\Atomic\TList
                    && !$namespaced_type_part instanceof Type\Atomic\TNonEmptyList
                    && $namespaced_type_part->type_param->isMixed())
                || ($namespaced_type_part instanceof Type\Atomic\TArray
                    && $namespaced_type_part->type_params[0]->isArrayKey()
                    && $namespaced_type_part->type_params[1]->isMixed())
                || ($namespaced_type_part instanceof Type\Atomic\TIterable
                    && $namespaced_type_part->type_params[0]->isMixed()
                    && $namespaced_type_part->type_params[1]->isMixed())
            ) {
                $assertion_type_parts[] = $prefix . $namespaced_type_part->getAssertionString();
            } else {
                $assertion_type_parts[] = $prefix . $namespaced_type_part->getId();
            }
        }

        return $assertion_type_parts;
    }

    /**
     * @param  PhpParser\Node\Param $param
     *
     * @return FunctionLikeParameter
     */
    public function getTranslatedFunctionParam(
        PhpParser\Node\Param $param,
        PhpParser\Node\FunctionLike $stmt,
        bool $fake_method,
        ?string $fq_classlike_name
    ) : FunctionLikeParameter {
        $param_type = null;

        $is_nullable = $param->default !== null &&
            $param->default instanceof PhpParser\Node\Expr\ConstFetch &&
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

                $this->codebase->scanner->queueClassLikeForScanning($param_type_string);
                $this->file_storage->referenced_classlikes[strtolower($param_type_string)] = $param_type_string;
            } elseif ($param_typehint instanceof PhpParser\Node\UnionType) {
                // not yet supported
                $param_type_string = 'mixed';
            } else {
                if ($this->classlike_storages
                    && strtolower($param_typehint->parts[0]) === 'self'
                    && !end($this->classlike_storages)->is_trait
                ) {
                    $param_type_string = $this->fq_classlike_names[count($this->fq_classlike_names) - 1];
                } else {
                    $param_type_string = ClassLikeAnalyzer::getFQCLNFromNameObject($param_typehint, $this->aliases);
                }

                if (!in_array(strtolower($param_type_string), ['self', 'static', 'parent'], true)) {
                    $this->codebase->scanner->queueClassLikeForScanning($param_type_string);
                    $this->file_storage->referenced_classlikes[strtolower($param_type_string)] = $param_type_string;
                }
            }

            if ($param_type_string) {
                $param_type = Type::parseString(
                    $param_type_string,
                    [$this->php_major_version, $this->php_minor_version],
                    []
                );

                if ($is_nullable) {
                    $param_type->addType(new Type\Atomic\TNull);
                }
            }
        }

        $is_optional = $param->default !== null;

        if ($param->var instanceof PhpParser\Node\Expr\Error || !is_string($param->var->name)) {
            throw new \UnexpectedValueException('Not expecting param name to be non-string');
        }

        return new FunctionLikeParameter(
            $param->var->name,
            $param->byRef,
            $param_type,
            new CodeLocation(
                $this->file_scanner,
                $fake_method ? $stmt : $param,
                null,
                false,
                !$fake_method
                    ? CodeLocation::FUNCTION_PARAM_VAR
                    : CodeLocation::FUNCTION_PHPDOC_METHOD
            ),
            $param_typehint
                ? new CodeLocation(
                    $this->file_scanner,
                    $fake_method ? $stmt : $param,
                    null,
                    false,
                    CodeLocation::FUNCTION_PARAM_TYPE
                )
                : null,
            $is_optional,
            $is_nullable,
            $param->variadic,
            $param->default
                ? StatementsAnalyzer::getSimpleType(
                    $this->codebase,
                    new \Psalm\Internal\Provider\NodeDataProvider(),
                    $param->default,
                    $this->aliases,
                    null,
                    null,
                    $fq_classlike_name
                )
                : null
        );
    }

    /**
     * @param  FunctionLikeStorage          $storage
     * @param  array<int, array{type:string,name:string,line_number:int,start:int,end:int}>  $docblock_params
     * @param  PhpParser\Node\FunctionLike  $function
     *
     * @return void
     */
    private function improveParamsFromDocblock(
        FunctionLikeStorage $storage,
        array $docblock_params,
        PhpParser\Node\FunctionLike $function,
        bool $fake_method,
        ?string $fq_classlike_name
    ) {
        $base = $this->fq_classlike_names
            ? $this->fq_classlike_names[count($this->fq_classlike_names) - 1] . '::'
            : '';

        $cased_method_id = $base . $storage->cased_name;

        $unused_docblock_params = [];

        $class_template_types = !$function instanceof PhpParser\Node\Stmt\ClassMethod || !$function->isStatic()
            ? $this->class_template_types
            : [];

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

            if (!$fake_method) {
                $docblock_type_location = new CodeLocation\DocblockTypeLocation(
                    $this->file_scanner,
                    $docblock_param['start'],
                    $docblock_param['end'],
                    $docblock_param['line_number']
                );
            } else {
                $docblock_type_location = new CodeLocation(
                    $this->file_scanner,
                    $function,
                    null,
                    false,
                    CodeLocation::FUNCTION_PHPDOC_METHOD,
                    null
                );
            }

            if ($storage_param === null) {
                $param_location = new CodeLocation(
                    $this->file_scanner,
                    $function,
                    null,
                    true,
                    CodeLocation::FUNCTION_PARAM_VAR
                );

                $param_location->setCommentLine($docblock_param['line_number']);
                $unused_docblock_params[$param_name] = $param_location;

                if (!$docblock_param_variadic || $storage->params || $this->scan_deep) {
                    continue;
                }

                $storage_param = new FunctionLikeParameter(
                    $param_name,
                    false,
                    null,
                    null,
                    null,
                    false,
                    false,
                    true,
                    null
                );

                $storage->params[] = $storage_param;
            }

            try {
                $new_param_type = Type::parseTokens(
                    Type::fixUpLocalType(
                        $docblock_param['type'],
                        $this->aliases,
                        $this->function_template_types + $class_template_types,
                        $this->type_aliases,
                        $fq_classlike_name
                    ),
                    null,
                    $this->function_template_types + $class_template_types
                );
            } catch (TypeParseTreeException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_method_id,
                    $docblock_type_location
                );

                continue;
            }

            $storage_param->has_docblock_type = true;
            $new_param_type->setFromDocblock();

            $new_param_type->queueClassLikesForScanning(
                $this->codebase,
                $this->file_storage,
                $storage->template_types ?: []
            );

            if ($storage->template_types) {
                foreach ($storage->template_types as $t => $type_map) {
                    foreach ($type_map as $obj => [$type]) {
                        if ($type->isMixed() && $docblock_param['type'] === 'class-string<' . $t . '>') {
                            $storage->template_types[$t][$obj] = [Type::getObject()];

                            if (isset($this->function_template_types[$t])) {
                                $this->function_template_types[$t][$obj] = $storage->template_types[$t][$obj];
                            }
                        }
                    }
                }
            }

            if (!$docblock_param_variadic && $storage_param->is_variadic && $new_param_type->hasArray()) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var Type\Atomic\TArray|Type\Atomic\ObjectLike|Type\Atomic\TList
                 */
                $array_type = $new_param_type->getAtomicTypes()['array'];

                if ($array_type instanceof Type\Atomic\ObjectLike) {
                    $new_param_type = $array_type->getGenericValueType();
                } elseif ($array_type instanceof Type\Atomic\TList) {
                    $new_param_type = $array_type->type_param;
                } else {
                    $new_param_type = $array_type->type_params[1];
                }
            }

            $existing_param_type_nullable = $storage_param->is_nullable;

            if (!$storage_param->type || $storage_param->type->hasMixed() || $storage->template_types) {
                if ($existing_param_type_nullable
                    && !$new_param_type->isNullable()
                    && !$new_param_type->hasTemplate()
                ) {
                    $new_param_type->addType(new Type\Atomic\TNull());
                }

                if ($this->config->add_param_default_to_docblock_type
                    && $storage_param->default_type
                    && !$storage_param->default_type->hasMixed()
                    && (!$storage_param->type || !$storage_param->type->hasMixed())
                ) {
                    $new_param_type = Type::combineUnionTypes($new_param_type, $storage_param->default_type);
                }

                $storage_param->type = $new_param_type;
                $storage_param->type_location = $docblock_type_location;
                continue;
            }

            $storage_param_atomic_types = $storage_param->type->getAtomicTypes();

            $all_typehint_types_match = true;

            foreach ($new_param_type->getAtomicTypes() as $key => $type) {
                if (isset($storage_param_atomic_types[$key])) {
                    $type->from_docblock = false;

                    if ($storage_param_atomic_types[$key] instanceof Type\Atomic\TArray
                        && $type instanceof Type\Atomic\TArray
                        && $type->type_params[0]->hasArrayKey()
                    ) {
                        $type->type_params[0]->from_docblock = false;
                    }
                } else {
                    $all_typehint_types_match = false;
                }
            }

            if ($all_typehint_types_match) {
                $new_param_type->from_docblock = false;
            }

            if ($existing_param_type_nullable && !$new_param_type->isNullable()) {
                $new_param_type->addType(new Type\Atomic\TNull());
            }

            $storage_param->type = $new_param_type;
            $storage_param->type_location = $docblock_type_location;
        }

        $params_without_docblock_type = array_filter(
            $storage->params,
            function (FunctionLikeParameter $p) : bool {
                return !$p->has_docblock_type && (!$p->type || $p->type->hasArray());
            }
        );

        if ($params_without_docblock_type) {
            $storage->unused_docblock_params = $unused_docblock_params;
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
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $comment,
                    $this->file_scanner,
                    $this->aliases,
                    $this->function_template_types + (!$stmt->isStatic() ? $this->class_template_types : []),
                    $this->type_aliases
                );

                $var_comment = array_pop($var_comments);
            } catch (IncorrectDocblockException $e) {
                $storage->docblock_issues[] = new MissingDocblockType(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );
            } catch (DocblockParseException $e) {
                $storage->docblock_issues[] = new InvalidDocblock(
                    $e->getMessage(),
                    new CodeLocation($this->file_scanner, $stmt, null, true)
                );
            }
        }

        $signature_type = null;
        $signature_type_location = null;

        if ($stmt->type) {
            $suffix = '';

            $parser_property_type = $stmt->type;

            if ($parser_property_type instanceof PhpParser\Node\NullableType) {
                $suffix = '|null';
                $parser_property_type = $parser_property_type->type;
            }

            if ($parser_property_type instanceof PhpParser\Node\Identifier) {
                $property_type_string = $parser_property_type->name . $suffix;
            } elseif ($parser_property_type instanceof PhpParser\Node\UnionType) {
                // not yet supported
                $property_type_string = 'mixed';
            } else {
                $property_type_fq_classlike_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $parser_property_type,
                    $this->aliases
                );

                $property_type_string = $property_type_fq_classlike_name . $suffix;
            }

            $signature_type = Type::parseString(
                $property_type_string,
                [$this->php_major_version, $this->php_minor_version]
            );
            $signature_type->queueClassLikesForScanning($this->codebase, $this->file_storage);

            $signature_type_location = new CodeLocation(
                $this->file_scanner,
                $parser_property_type,
                null,
                false,
                CodeLocation::FUNCTION_RETURN_TYPE
            );
        }

        $doc_var_group_type = $var_comment ? $var_comment->type : null;

        if ($doc_var_group_type) {
            $doc_var_group_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
            $doc_var_group_type->setFromDocblock();
        }

        foreach ($stmt->props as $property) {
            $doc_var_location = null;

            $property_storage = $storage->properties[$property->name->name] = new PropertyStorage();
            $property_storage->is_static = $stmt->isStatic();
            $property_storage->type = $signature_type;
            $property_storage->signature_type = $signature_type;
            $property_storage->signature_type_location = $signature_type_location;
            $property_storage->type_location = $signature_type_location;
            $property_storage->location = new CodeLocation($this->file_scanner, $property->name);
            $property_storage->stmt_location = new CodeLocation($this->file_scanner, $stmt);
            $property_storage->has_default = $property->default ? true : false;
            $property_storage->deprecated = $var_comment ? $var_comment->deprecated : false;
            $property_storage->internal = $var_comment ? $var_comment->internal : false;
            $property_storage->psalm_internal = $var_comment ? $var_comment->psalm_internal : null;
            $property_storage->readonly = $var_comment ? $var_comment->readonly : false;
            $property_storage->allow_private_mutation = $var_comment ? $var_comment->allow_private_mutation : false;

            if (!$signature_type && !$doc_var_group_type) {
                if ($property->default) {
                    $property_storage->suggested_type = StatementsAnalyzer::getSimpleType(
                        $this->codebase,
                        new \Psalm\Internal\Provider\NodeDataProvider(),
                        $property->default,
                        $this->aliases,
                        null,
                        $existing_constants,
                        $fq_classlike_name
                    );
                }

                $property_storage->type = null;
            } else {
                if ($var_comment
                    && $var_comment->type_start
                    && $var_comment->type_end
                    && $var_comment->line_number
                ) {
                    $doc_var_location = new CodeLocation\DocblockTypeLocation(
                        $this->file_scanner,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number
                    );
                }

                if ($doc_var_group_type) {
                    $property_storage->type = count($stmt->props) === 1
                        ? $doc_var_group_type
                        : clone $doc_var_group_type;
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
                            $type->from_docblock = false;
                        } else {
                            $all_typehint_types_match = false;
                        }
                    }

                    if ($all_typehint_types_match) {
                        $property_storage->type->from_docblock = false;
                    }

                    if ($property_storage->signature_type->isNullable()
                        && !$property_storage->type->isNullable()
                    ) {
                        $property_storage->type->addType(new Type\Atomic\TNull());
                    }
                }

                $property_storage->type->queueClassLikesForScanning($this->codebase, $this->file_storage);
            }

            if ($stmt->isPublic()) {
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
            } elseif ($stmt->isProtected()) {
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            } elseif ($stmt->isPrivate()) {
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
            }

            $fq_classlike_name = $this->fq_classlike_names[count($this->fq_classlike_names) - 1];

            $property_id = $fq_classlike_name . '::$' . $property->name->name;

            $storage->declaring_property_ids[$property->name->name] = $fq_classlike_name;
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

        $comment = $stmt->getDocComment();
        $deprecated = false;
        $config = $this->config;

        if ($comment && $comment->getText() && ($config->use_docblock_types || $config->use_docblock_property_types)) {
            $comments = DocComment::parsePreservingLength($comment);

            if (isset($comments['specials']['deprecated'])) {
                $deprecated = true;
            }
        }

        foreach ($stmt->consts as $const) {
            $const_type = StatementsAnalyzer::getSimpleType(
                $this->codebase,
                new \Psalm\Internal\Provider\NodeDataProvider(),
                $const->value,
                $this->aliases,
                null,
                $existing_constants,
                $fq_classlike_name
            );

            if ($const_type) {
                $existing_constants[$const->name->name] = $const_type;

                if ($stmt->isProtected()) {
                    $storage->protected_class_constants[$const->name->name] = $const_type;
                } elseif ($stmt->isPrivate()) {
                    $storage->private_class_constants[$const->name->name] = $const_type;
                } else {
                    $storage->public_class_constants[$const->name->name] = $const_type;
                }

                $storage->class_constant_locations[$const->name->name] = new CodeLocation(
                    $this->file_scanner,
                    $const->name
                );

                $storage->class_constant_stmt_locations[$const->name->name] = new CodeLocation(
                    $this->file_scanner,
                    $const
                );
            } else {
                $unresolved_const_expr = self::getUnresolvedClassConstExpr(
                    $const->value,
                    $this->aliases,
                    $fq_classlike_name
                );

                if ($stmt->isProtected()) {
                    if ($unresolved_const_expr) {
                        $storage->protected_class_constant_nodes[$const->name->name] = $unresolved_const_expr;
                    } else {
                        $storage->protected_class_constants[$const->name->name] = Type::getMixed();
                    }
                } elseif ($stmt->isPrivate()) {
                    if ($unresolved_const_expr) {
                        $storage->private_class_constant_nodes[$const->name->name] = $unresolved_const_expr;
                    } else {
                        $storage->private_class_constants[$const->name->name] = Type::getMixed();
                    }
                } else {
                    if ($unresolved_const_expr) {
                        $storage->public_class_constant_nodes[$const->name->name] = $unresolved_const_expr;
                    } else {
                        $storage->public_class_constants[$const->name->name] = Type::getMixed();
                    }
                }
            }

            if ($deprecated) {
                $storage->deprecated_constants[$const->name->name] = true;
            }
        }
    }

    public function getUnresolvedClassConstExpr(
        PhpParser\Node\Expr $stmt,
        Aliases $aliases,
        string $fq_classlike_name
    ) : ?UnresolvedConstantComponent {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            $left = self::getUnresolvedClassConstExpr(
                $stmt->left,
                $aliases,
                $fq_classlike_name
            );

            $right = self::getUnresolvedClassConstExpr(
                $stmt->right,
                $aliases,
                $fq_classlike_name
            );

            if (!$left || !$right) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus) {
                return new UnresolvedConstant\UnresolvedAdditionOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
                return new UnresolvedConstant\UnresolvedSubtractionOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul) {
                return new UnresolvedConstant\UnresolvedMultiplicationOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
                return new UnresolvedConstant\UnresolvedDivisionOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                return new UnresolvedConstant\UnresolvedConcatOp($left, $right);
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
                return new UnresolvedConstant\UnresolvedBitwiseOr($left, $right);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            $cond = self::getUnresolvedClassConstExpr(
                $stmt->cond,
                $aliases,
                $fq_classlike_name
            );

            $if = null;

            if ($stmt->if) {
                $if = self::getUnresolvedClassConstExpr(
                    $stmt->if,
                    $aliases,
                    $fq_classlike_name
                );

                if ($if === null) {
                    $if = false;
                }
            }

            $else = self::getUnresolvedClassConstExpr(
                $stmt->else,
                $aliases,
                $fq_classlike_name
            );

            if ($cond && $else && $if !== false) {
                return new UnresolvedConstant\UnresolvedTernary($cond, $if, $else);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (strtolower($stmt->name->parts[0]) === 'false') {
                return new UnresolvedConstant\ScalarValue(false);
            } elseif (strtolower($stmt->name->parts[0]) === 'true') {
                return new UnresolvedConstant\ScalarValue(true);
            } elseif (strtolower($stmt->name->parts[0]) === 'null') {
                return new UnresolvedConstant\ScalarValue(null);
            } elseif ($stmt->name->parts[0] === '__NAMESPACE__') {
                return new UnresolvedConstant\ScalarValue($aliases->namespace);
            }

            return new UnresolvedConstant\Constant(
                implode('\\', $stmt->name->parts),
                $stmt->name instanceof PhpParser\Node\Name\FullyQualified
            );
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
            return new UnresolvedConstant\ScalarValue($aliases->namespace);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $stmt->dim) {
            $left = self::getUnresolvedClassConstExpr(
                $stmt->var,
                $aliases,
                $fq_classlike_name
            );

            $right = self::getUnresolvedClassConstExpr(
                $stmt->dim,
                $aliases,
                $fq_classlike_name
            );

            if ($left && $right) {
                return new UnresolvedConstant\ArrayOffsetFetch($left, $right);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if ($stmt->class instanceof PhpParser\Node\Name
                && $stmt->name instanceof PhpParser\Node\Identifier
                && $fq_classlike_name
                && $stmt->class->parts !== ['static']
                && $stmt->class->parts !== ['parent']
            ) {
                if ($stmt->class->parts === ['self']) {
                    $const_fq_class_name = $fq_classlike_name;
                } else {
                    $const_fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $aliases
                    );
                }

                return new UnresolvedConstant\ClassConstant($const_fq_class_name, $stmt->name->name);
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_
            || $stmt instanceof PhpParser\Node\Scalar\LNumber
            || $stmt instanceof PhpParser\Node\Scalar\DNumber
        ) {
            return new UnresolvedConstant\ScalarValue($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            $items = [];

            foreach ($stmt->items as $item) {
                if ($item === null) {
                    return null;
                }

                if ($item->key) {
                    $item_key_type = self::getUnresolvedClassConstExpr(
                        $item->key,
                        $aliases,
                        $fq_classlike_name
                    );

                    if (!$item_key_type) {
                        return null;
                    }
                } else {
                    $item_key_type = null;
                }

                $item_value_type = self::getUnresolvedClassConstExpr(
                    $item->value,
                    $aliases,
                    $fq_classlike_name
                );

                if (!$item_value_type) {
                    return null;
                }

                $items[] = new UnresolvedConstant\KeyValuePair($item_key_type, $item_value_type);
            }

            return new UnresolvedConstant\ArrayValue($items);
        }

        return null;
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
            $include_path = IncludeAnalyzer::resolveIncludePath($path_to_file, dirname($this->file_path));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if (DIRECTORY_SEPARATOR === '/') {
                $is_path_relative = $path_to_file[0] !== DIRECTORY_SEPARATOR;
            } else {
                $is_path_relative = !preg_match('~^[A-Z]:\\\\~i', $path_to_file);
            }

            if ($is_path_relative) {
                $path_to_file = $config->base_dir . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = IncludeAnalyzer::getPathTo(
                $stmt->expr,
                null,
                $this->file_path,
                $this->config
            );
        }

        if ($path_to_file) {
            $path_to_file = IncludeAnalyzer::normalizeFilePath($path_to_file);

            if ($this->file_path === $path_to_file) {
                return;
            }

            if ($this->codebase->fileExists($path_to_file)) {
                if ($this->scan_deep) {
                    $this->codebase->scanner->addFileToDeepScan($path_to_file);
                } else {
                    $this->codebase->scanner->addFileToShallowScan($path_to_file);
                }

                $this->file_storage->required_file_paths[strtolower($path_to_file)] = $path_to_file;

                return;
            }
        }
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
    public function getRootFilePath()
    {
        return $this->file_scanner->getRootFilePath();
    }

    /**
     * @return string
     */
    public function getRootFileName()
    {
        return $this->file_scanner->getRootFileName();
    }

    /**
     * @return Aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    public function afterTraverse(array $nodes)
    {
        $this->file_storage->type_aliases = $this->type_aliases;
    }
}
