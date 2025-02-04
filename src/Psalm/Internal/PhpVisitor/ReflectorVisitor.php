<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use LogicException;
use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Exception\CodeException;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\EventDispatcher;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeNodeScanner;
use Psalm\Internal\PhpVisitor\Reflector\ExpressionResolver;
use Psalm\Internal\PhpVisitor\Reflector\ExpressionScanner;
use Psalm\Internal\PhpVisitor\Reflector\FunctionLikeNodeScanner;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Internal\Scanner\PhpStormMetaScanner;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeParser;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\TaintedInput;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Psalm\Storage\FileStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use SplObjectStorage;
use UnexpectedValueException;

use function array_pop;
use function end;
use function explode;
use function in_array;
use function is_string;
use function reset;
use function spl_object_id;
use function strpos;
use function strtolower;

/**
 * @internal
 */
final class ReflectorVisitor extends PhpParser\NodeVisitorAbstract implements FileSource
{
    private Aliases $aliases;

    private readonly string $file_path;

    private readonly bool $scan_deep;

    /**
     * @var array<FunctionLikeNodeScanner>
     */
    private array $functionlike_node_scanners = [];

    /**
     * @var array<ClassLikeNodeScanner>
     */
    private array $classlike_node_scanners = [];

    private ?Name $namespace_name = null;

    private ?Expr $exists_cond_expr = null;

    private ?int $skip_if_descendants = null;

    /**
     * @var array<string, TypeAlias>
     */
    private array $type_aliases = [];

    /**
     * @var array<int, bool>
     */
    private array $bad_classes = [];
    private readonly EventDispatcher $eventDispatcher;

    /**
     * @var SplObjectStorage<PhpParser\Node\FunctionLike, null>
     */
    private readonly SplObjectStorage $closure_statements;

    public function __construct(
        private readonly Codebase $codebase,
        private readonly FileScanner $file_scanner,
        private readonly FileStorage $file_storage,
    ) {
        $this->file_path = $file_scanner->file_path;
        $this->scan_deep = $file_scanner->will_analyze;
        $this->aliases = $this->file_storage->aliases = new Aliases();
        $this->eventDispatcher = $this->codebase->config->eventDispatcher;
        $this->closure_statements = new SplObjectStorage();
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        foreach ($node->getComments() as $comment) {
            if ($comment instanceof PhpParser\Comment\Doc && !$node instanceof PhpParser\Node\Stmt\ClassLike) {
                try {
                    $type_aliases = ClassLikeNodeScanner::getTypeAliasesFromComment(
                        $comment,
                        $this->aliases,
                        $this->type_aliases,
                        null,
                    );

                    foreach ($type_aliases as $type_alias) {
                        // finds issues, if there are any
                        TypeParser::parseTokens($type_alias->replacement_tokens);
                    }

                    $this->type_aliases += $type_aliases;
                } catch (DocblockParseException | TypeParseTreeException $e) {
                    $this->file_storage->docblock_issues[] = new InvalidDocblock(
                        $e->getMessage(),
                        new CodeLocation($this->file_scanner, $node, null, true),
                    );
                }
            }
        }

        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->handleNamespace($node);
        } elseif ($node instanceof PhpParser\Node\Stmt\Use_) {
            $this->handleUse($node);
        } elseif ($node instanceof PhpParser\Node\Stmt\GroupUse) {
            $this->handleGroupUse($node);
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if ($this->skip_if_descendants) {
                return null;
            }

            $classlike_node_scanner = new ClassLikeNodeScanner(
                $this->codebase,
                $this->file_storage,
                $this->file_scanner,
                $this->aliases,
                $this->namespace_name,
            );

            if ($classlike_node_scanner->start($node) === false) {
                $this->bad_classes[spl_object_id($node)] = true;
                return self::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }

            $this->classlike_node_scanners[] = $classlike_node_scanner;

            $this->type_aliases = [...$this->type_aliases, ...$classlike_node_scanner->type_aliases];
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
        } elseif ($node instanceof PhpParser\Node\FunctionLike
                  || $node instanceof PhpParser\Node\Stmt\Expression
                     && ($node->expr instanceof PhpParser\Node\Expr\ArrowFunction
                         || $node->expr instanceof PhpParser\Node\Expr\Closure)
                  || $node instanceof PhpParser\Node\Arg
                     && ($node->value instanceof PhpParser\Node\Expr\ArrowFunction
                         || $node->value instanceof PhpParser\Node\Expr\Closure)
         ) {
            $doc_comment = null;
            if ($node instanceof PhpParser\Node\Stmt\Function_
                || $node instanceof PhpParser\Node\Stmt\ClassMethod
            ) {
                if ($this->skip_if_descendants) {
                    return null;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Expression) {
                $doc_comment = $node->getDocComment();
                /** @var PhpParser\Node\FunctionLike */
                $node = $node->expr;
                $this->closure_statements->attach($node);
            } elseif ($node instanceof PhpParser\Node\Arg) {
                $doc_comment = $node->getDocComment();
                /** @var PhpParser\Node\FunctionLike */
                $node = $node->value;
                $this->closure_statements->attach($node);
            } elseif ($this->closure_statements->contains($node)) {
                // This is a closure that was already processed at the statement level.
                return null;
            }

            $classlike_storage = null;

            if ($this->classlike_node_scanners) {
                $classlike_node_scanner = end($this->classlike_node_scanners);
                $classlike_storage = $classlike_node_scanner->storage;
            }

            $functionlike_types = [];

            foreach ($this->functionlike_node_scanners as $functionlike_node_scanner) {
                $functionlike_storage = $functionlike_node_scanner->storage;
                $functionlike_types += $functionlike_storage->template_types ?? [];
            }

            $functionlike_node_scanner = new FunctionLikeNodeScanner(
                $this->codebase,
                $this->file_scanner,
                $this->file_storage,
                $this->aliases,
                $this->type_aliases,
                $classlike_storage,
                $functionlike_types,
            );

            $functionlike_node_scanner->start($node, false, $doc_comment);

            $this->functionlike_node_scanners[] = $functionlike_node_scanner;

            if ($classlike_storage
                && $this->codebase->analysis_php_version_id >= 8_00_00
                && $node instanceof PhpParser\Node\Stmt\ClassMethod
                && strtolower($node->name->name) === '__tostring'
            ) {
                if ($classlike_storage->is_interface) {
                    $classlike_storage->parent_interfaces['stringable'] = 'Stringable';
                } else {
                    $classlike_storage->class_implements['stringable'] = 'Stringable';
                }

                $this->codebase->scanner->queueClassLikeForScanning('Stringable');
            }

            if (!$this->scan_deep) {
                return self::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Global_) {
            $functionlike_node_scanner = end($this->functionlike_node_scanners);

            if ($functionlike_node_scanner && $functionlike_node_scanner->storage) {
                foreach ($node->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name) && $var->name !== 'argv' && $var->name !== 'argc') {
                            $var_id = '$' . $var->name;

                            $functionlike_node_scanner->storage->global_variables[$var_id] = true;

                            if (isset($this->codebase->config->globals[$var_id])) {
                                $var_type = Type::parseString($this->codebase->config->globals[$var_id]);
                                /** @psalm-suppress UnusedMethodCall */
                                $var_type->queueClassLikesForScanning($this->codebase, $this->file_storage);
                            }
                        }
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\TraitUse) {
            if ($this->skip_if_descendants) {
                return null;
            }

            if (!$this->classlike_node_scanners) {
                throw new LogicException('$this->classlike_node_scanners should not be empty');
            }

            $classlike_node_scanner = end($this->classlike_node_scanners);

            $classlike_node_scanner->handleTraitUse($node);
        } elseif ($node instanceof PhpParser\Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $const_type = SimpleTypeInferer::infer(
                    $this->codebase,
                    new NodeDataProvider(),
                    $const->value,
                    $this->aliases,
                ) ?? Type::getMixed();

                $fq_const_name = Type::getFQCLNFromString($const->name->name, $this->aliases);

                if ($this->codebase->register_stub_files || $this->codebase->register_autoload_files) {
                    $this->codebase->addGlobalConstantType($fq_const_name, $const_type);
                }

                $this->file_storage->constants[$fq_const_name] = $const_type;
                $this->file_storage->declaring_constants[$fq_const_name] = $this->file_path;
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\If_ && !$this->skip_if_descendants) {
            if (!$this->functionlike_node_scanners) {
                $this->exists_cond_expr = $node->cond;

                if (ExpressionResolver::enterConditional(
                    $this->codebase,
                    $this->file_path,
                    $this->exists_cond_expr,
                ) === false
                ) {
                    // the else node should terminate the agreement
                    $this->skip_if_descendants = $node->else ? $node->else->getLine() : $node->getLine();
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\Else_) {
            if ($this->skip_if_descendants === $node->getLine()) {
                $this->skip_if_descendants = null;
                $this->exists_cond_expr = null;
            } elseif (!$this->skip_if_descendants) {
                if ($this->exists_cond_expr
                    && ExpressionResolver::enterConditional(
                        $this->codebase,
                        $this->file_path,
                        $this->exists_cond_expr,
                    ) === true
                ) {
                    $this->skip_if_descendants = $node->getLine();
                }
            }
        } elseif ($node instanceof Expr) {
            $functionlike_storage = null;

            if ($this->functionlike_node_scanners) {
                $functionlike_node_scanner = end($this->functionlike_node_scanners);
                $functionlike_storage = $functionlike_node_scanner->storage;
            }

            ExpressionScanner::scan(
                $this->codebase,
                $this->file_scanner,
                $this->file_storage,
                $this->aliases,
                $node,
                $functionlike_storage,
                $this->skip_if_descendants,
            );
        }

        if ($doc_comment = $node->getDocComment()) {
            $var_comments = [];

            $template_types = [];

            if ($this->classlike_node_scanners) {
                $classlike_node_scanner = end($this->classlike_node_scanners);
                $classlike_storage = $classlike_node_scanner->storage;
                $template_types = $classlike_storage->template_types ?? [];
            }

            foreach ($this->functionlike_node_scanners as $functionlike_node_scanner) {
                $functionlike_storage = $functionlike_node_scanner->storage;
                $template_types += $functionlike_storage->template_types ?? [];
            }

            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment,
                    $this->file_scanner,
                    $this->aliases,
                    $template_types,
                    $this->type_aliases,
                );
            } catch (DocblockParseException) {
                // do nothing
            }

            foreach ($var_comments as $var_comment) {
                if (!$var_comment->type) {
                    continue;
                }

                $var_type = $var_comment->type;
                /** @psalm-suppress UnusedMethodCall */
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
                if ($this->functionlike_node_scanners) {
                    $functionlike_node_scanner = end($this->functionlike_node_scanners);
                    $functionlike_storage = $functionlike_node_scanner->storage;

                    if ($functionlike_storage instanceof MethodStorage) {
                        $functionlike_storage->this_property_mutations[$node->var->name->name] = true;
                    }
                }
            }
        }

        return null;
    }

    private function handleNamespace(PhpParser\Node\Stmt\Namespace_ $node): void
    {
        $this->file_storage->aliases = $this->aliases;

        $this->namespace_name = $node->name;

        $this->aliases = new Aliases(
            $node->name ? $node->name->toString() : '',
            $this->aliases->uses,
            $this->aliases->functions,
            $this->aliases->constants,
            $this->aliases->uses_flipped,
            $this->aliases->functions_flipped,
            $this->aliases->constants_flipped,
        );

        $this->file_storage->namespace_aliases[(int) $node->getAttribute('startFilePos')] = $this->aliases;

        if ($node->stmts) {
            $this->aliases->namespace_first_stmt_start = (int) $node->stmts[0]->getAttribute('startFilePos');
        }
    }

    private function handleUse(PhpParser\Node\Stmt\Use_ $node): void
    {
        foreach ($node->uses as $use) {
            $use_path = $use->name->toString();

            $use_alias = $use->alias->name ?? $use->name->getLast();

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
    }

    private function handleGroupUse(PhpParser\Node\Stmt\GroupUse $node): void
    {
        $use_prefix = $node->prefix->toString();

        foreach ($node->uses as $use) {
            $use_path = $use_prefix . '\\' . $use->name->toString();
            $use_alias = $use->alias->name ?? $use->name->getLast();

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
    }

    /**
     * @return null
     */
    public function leaveNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            if (!$this->file_storage->aliases) {
                throw new UnexpectedValueException('File storage liases should not be null');
            }

            $this->aliases = $this->file_storage->aliases;

            if ($this->codebase->register_stub_files
                && $node->name
                && $node->name->getParts() === ['PHPSTORM_META']
            ) {
                foreach ($node->stmts as $meta_stmt) {
                    if ($meta_stmt instanceof PhpParser\Node\Stmt\Expression
                        && $meta_stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                        && $meta_stmt->expr->name instanceof Name
                        && $meta_stmt->expr->name->getParts() === ['override']
                    ) {
                        PhpStormMetaScanner::handleOverride($meta_stmt->expr->getArgs(), $this->codebase);
                    }
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike) {
            if ($this->skip_if_descendants) {
                return null;
            }

            if (isset($this->bad_classes[spl_object_id($node)])) {
                return null;
            }

            if (!$this->classlike_node_scanners) {
                throw new UnexpectedValueException('$this->classlike_node_scanners cannot be empty');
            }

            $classlike_node_scanner = array_pop($this->classlike_node_scanners);

            $classlike_storage = $classlike_node_scanner->finish($node);

            if ($classlike_storage->has_visitor_issues) {
                $this->file_storage->has_visitor_issues = true;
            }

            $event = new AfterClassLikeVisitEvent(
                $node,
                $classlike_storage,
                $this,
                $this->codebase,
                [],
            );

            $this->eventDispatcher->dispatchAfterClassLikeVisit($event);

            if (!$this->file_storage->has_visitor_issues) {
                $this->codebase->cacheClassLikeStorage($classlike_storage, $this->file_path);
            }
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            if ($this->skip_if_descendants) {
                return null;
            }

            if (!$this->functionlike_node_scanners) {
                if ($this->file_storage->has_visitor_issues) {
                    return null;
                }

                throw new UnexpectedValueException(
                    'There should be function storages for line ' . $this->file_path . ':' . $node->getStartLine(),
                );
            }

            $functionlike_node_scanner = array_pop($this->functionlike_node_scanners);

            if ($functionlike_node_scanner->storage) {
                foreach ($functionlike_node_scanner->storage->docblock_issues as $docblock_issue) {
                    if (strpos($docblock_issue->code_location->file_path, 'CoreGenericFunctions.phpstub')
                        || strpos($docblock_issue->code_location->file_path, 'CoreGenericClasses.phpstub')
                        || strpos($this->file_path, 'CoreGenericIterators.phpstub')
                    ) {
                        $e = reset($functionlike_node_scanner->storage->docblock_issues);

                        $fqcn_parts = explode('\\', $e::class);
                        $issue_type = array_pop($fqcn_parts);

                        $message = $e instanceof TaintedInput
                            ? $e->getJourneyMessage()
                            : $e->message;

                        throw new CodeException(
                            'Error with core stub file docblocks: '
                                . $issue_type
                                . ' - ' . $e->getShortLocationWithPrevious()
                                . ':' . $e->code_location->getColumn()
                                . ' - ' . $message,
                        );
                    }
                }

                if ($functionlike_node_scanner->storage->has_visitor_issues) {
                    $this->file_storage->has_visitor_issues = true;
                }
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

    /** @psalm-mutation-free */
    public function getFilePath(): string
    {
        return $this->file_path;
    }

    /** @psalm-mutation-free */
    public function getFileName(): string
    {
        return $this->file_scanner->getFileName();
    }

    /** @psalm-mutation-free */
    public function getRootFilePath(): string
    {
        return $this->file_scanner->getRootFilePath();
    }

    /** @psalm-mutation-free */
    public function getRootFileName(): string
    {
        return $this->file_scanner->getRootFileName();
    }

    /** @psalm-mutation-free */
    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function afterTraverse(array $nodes)
    {
        $this->file_storage->type_aliases = $this->type_aliases;

        return null;
    }
}
