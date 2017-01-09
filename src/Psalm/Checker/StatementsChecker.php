<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\Block\ForChecker;
use Psalm\Checker\Statements\Block\ForeachChecker;
use Psalm\Checker\Statements\Block\IfChecker;
use Psalm\Checker\Statements\Block\SwitchChecker;
use Psalm\Checker\Statements\Block\TryChecker;
use Psalm\Checker\Statements\Block\WhileChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\Statements\Expression\CallChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\FileIncludeException;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\Issue\InvalidGlobal;
use Psalm\Issue\InvalidNamespace;
use Psalm\Issue\UnrecognizedStatement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;

class StatementsChecker extends SourceChecker implements StatementsSource
{
    /**
     * @var StatementsSource
     */
    protected $source;

    /**
     * @var array<string, int>
     */
    protected $all_vars = [];

    /**
     * @var array<string, array<string, Type\Union>>
     */
    public static $user_constants = [];

    /**
     * @param StatementsSource $source
     */
    public function __construct(StatementsSource $source)
    {
        $this->source = $source;

        $config = Config::getInstance();
    }

    /**
     * Checks an array of statements for validity
     *
     * @param  array<PhpParser\Node\Stmt|PhpParser\Node\Expr>   $stmts
     * @param  Context                                          $context
     * @param  Context|null                                     $loop_context
     * @param  Context|null                                     $global_context
     * @return null|false
     */
    public function analyze(
        array $stmts,
        Context $context,
        Context $loop_context = null,
        Context $global_context = null
    ) {
        $has_returned = false;

        $function_checkers = [];

        // hoist functions to the top
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->source);
                $function_checkers[$stmt->name] = $function_checker;
            }
        }

        foreach ($stmts as $stmt) {
            $plugins = Config::getInstance()->getPlugins();

            if ($plugins) {
                $code_location = new CodeLocation($this->source, $stmt);

                foreach ($plugins as $plugin) {
                    if ($plugin->checkStatement(
                        $this,
                        $stmt,
                        $context,
                        $code_location,
                        $this->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }
            }


            if ($has_returned && !($stmt instanceof PhpParser\Node\Stmt\Nop) &&
                !($stmt instanceof PhpParser\Node\Stmt\InlineHTML)
            ) {
                echo('Warning: Expressions after return/throw/continue in ' . $this->getCheckedFileName() . ' on line ' .
                    $stmt->getLine() . PHP_EOL);
                break;
            }

            /*
            if (isset($context->vars_in_scope['$storage->return_type_location'])) {
                var_dump($stmt->getLine() . ' ' . $context->vars_in_scope['$storage->return_type_location']);
            }
            */

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                IfChecker::analyze($this, $stmt, $context, $loop_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                TryChecker::analyze($this, $stmt, $context, $loop_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                ForChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                ForeachChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                WhileChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $this->analyzeDo($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                $this->analyzeConstAssignment($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                foreach ($stmt->vars as $var) {
                    $var_id = ExpressionChecker::getArrayVarId(
                        $var,
                        $this->getFQCLN(),
                        $this
                    );

                    if ($var_id) {
                        $context->remove($var_id);
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                $this->analyzeReturn($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $has_returned = true;
                $this->analyzeThrow($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                SwitchChecker::analyze($this, $stmt, $context, $loop_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                if ($loop_context === null) {
                    if (IssueBuffer::accepts(
                        new ContinueOutsideLoop(
                            'Continue call outside loop context',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                $has_returned = true;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Static_) {
                $this->analyzeStatic($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
                foreach ($stmt->exprs as $i => $expr) {
                    ExpressionChecker::analyze($this, $expr, $context);

                    if (isset($expr->inferredType)) {
                        if (CallChecker::checkFunctionArgumentType(
                            $this,
                            $expr->inferredType,
                            Type::getString(),
                            'echo',
                            (int)$i,
                            new CodeLocation($this->getSource(), $expr)
                        ) === false) {
                            return false;
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_context = new Context($this->getFileName(), $context->self);
                $function_checkers[$stmt->name]->analyze($function_context, $context);

                $config = Config::getInstance();

                if (!$config->excludeIssueInFile('InvalidReturnType', $this->getFileName())) {
                    /** @var string */
                    $method_id = $function_checkers[$stmt->name]->getMethodId();

                    $return_type = FunctionChecker::getFunctionReturnType(
                        $method_id,
                        $this->getFilePath()
                    );

                    $return_type_location = FunctionChecker::getFunctionReturnTypeLocation(
                        $method_id,
                        $this->getFilePath()
                    );

                    $function_checkers[$stmt->name]->verifyReturnType(
                        false,
                        $return_type,
                        $this->getFQCLN(),
                        $return_type_location
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr) {
                ExpressionChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\InlineHTML) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Global_) {
                if (!$global_context) {
                    if (IssueBuffer::accepts(
                        new InvalidGlobal(
                            'Cannot use global scope here',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    foreach ($stmt->vars as $var) {
                        if ($var instanceof PhpParser\Node\Expr\Variable) {
                            if (is_string($var->name)) {
                                $var_id = '$' . $var->name;

                                $context->vars_in_scope[$var_id] = isset($global_context->vars_in_scope[$var_id])
                                    ? clone $global_context->vars_in_scope[$var_id]
                                    : Type::getMixed();

                                $context->vars_possibly_in_scope[$var_id] = true;
                            } else {
                                ExpressionChecker::analyze($this, $var, $context);
                            }
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        ExpressionChecker::analyze($this, $prop->default, $context);

                        if (isset($prop->default->inferredType)) {
                            if (!$stmt->isStatic()) {
                                if (AssignmentChecker::analyzePropertyAssignment(
                                    $this,
                                    $prop,
                                    $prop->name,
                                    $prop->default,
                                    $prop->default->inferredType,
                                    $context
                                ) === false) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $const_visibility = \ReflectionProperty::IS_PUBLIC;

                if ($stmt->isProtected()) {
                    $const_visibility = \ReflectionProperty::IS_PROTECTED;
                }

                if ($stmt->isPrivate()) {
                    $const_visibility = \ReflectionProperty::IS_PRIVATE;
                }

                foreach ($stmt->consts as $const) {
                    ExpressionChecker::analyze($this, $const->value, $context);

                    if (isset($const->value->inferredType) && !$const->value->inferredType->isMixed()) {
                        ClassLikeChecker::setConstantType(
                            (string)$this->getFQCLN(),
                            $const->name,
                            $const->value->inferredType,
                            $const_visibility
                        );
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                (new ClassChecker($stmt, $this->source, $stmt->name))->visit();
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                CommentChecker::getTypeFromComment(
                    (string)$stmt->getDocComment(),
                    $context,
                    $this->getSource()
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Goto_) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Label) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Declare_) {
                // do nothing
            } else {
                if (IssueBuffer::accepts(
                    new UnrecognizedStatement(
                        'Psalm does not understand ' . get_class($stmt),
                        new CodeLocation($this->source, $stmt)
                    ),
                    $this->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Static_ $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected function analyzeStatic(PhpParser\Node\Stmt\Static_ $stmt, Context $context)
    {
        foreach ($stmt->vars as $var) {
            if ($var->default) {
                if (ExpressionChecker::analyze($this, $var->default, $context) === false) {
                    return false;
                }
            }

            if ($context->check_variables) {
                $context->vars_in_scope['$' . $var->name] = $var->default && isset($var->default->inferredType)
                    ? $var->default->inferredType
                    : Type::getMixed();

                $context->vars_possibly_in_scope['$' . $var->name] = true;
                $this->registerVariable('$' . $var->name, $var->getLine());
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr $stmt
     * @return  Type\Union|null
     */
    public static function getSimpleType(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            // @todo support this
        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            // @todo support this as well
        } elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return Type::getString();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            return Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            return Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (count($stmt->items) === 0) {
                return Type::getEmptyArray();
            }

            return Type::getArray();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            return Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            return Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            return Type::getString();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            return Type::getObject();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            return Type::getArray();
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return self::getSimpleType($stmt->expr);
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Do_ $stmt
     * @param   Context                 $context
     * @return  false|null
     */
    protected function analyzeDo(PhpParser\Node\Stmt\Do_ $stmt, Context $context)
    {
        // do not clone context for do, because it executes in current scope always
        if ($this->analyze($stmt->stmts, $context, $context) === false) {
            return false;
        }

        return ExpressionChecker::analyze($this, $stmt->cond, $context);
    }

    /**
     * @param  string  $method_id
     * @param  Context $context
     * @return void
     */
    public function checkInsideMethod($method_id, Context $context)
    {
        /**
        $method_checker = ClassLikeChecker::getMethodChecker($method_id);

        if ($method_checker &&
            $this->source instanceof FunctionLikeChecker &&
            $method_checker->getMethodId() !== $this->source->getMethodId()
        ) {
            $this_context = new Context($this->file_name, (string) $context->vars_in_scope['$this']);

            foreach ($context->vars_possibly_in_scope as $var => $type) {
                if (strpos($var, '$this->') === 0) {
                    $this_context->vars_possibly_in_scope[$var] = true;
                }
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, '$this->') === 0) {
                    $this_context->vars_in_scope[$var] = $type;
                }
            }

            $this_context->vars_in_scope['$this'] = $context->vars_in_scope['$this'];

            $method_checker->analyze($this_context);

            foreach ($this_context->vars_in_scope as $var => $type) {
                $context->vars_possibly_in_scope[$var] = true;
            }

            foreach ($this_context->vars_in_scope as $var => $type) {
                $context->vars_in_scope[$var] = $type;
            }
        }
        **/
    }

    /**
     * @param   PhpParser\Node\Stmt\Const_  $stmt
     * @param   Context                     $context
     * @return  void
     */
    protected function analyzeConstAssignment(PhpParser\Node\Stmt\Const_ $stmt, Context $context)
    {
        foreach ($stmt->consts as $const) {
            ExpressionChecker::analyze($this, $const->value, $context);

            $this->setConstType(
                $const->name,
                isset($const->value->inferredType) ? $const->value->inferredType : Type::getMixed(),
                $context
            );
        }
    }

    /**
     * @param   string  $const_name
     * @param   bool    $is_fully_qualified
     * @param   Context $context
     * @return  Type\Union|null
     */
    public function getConstType($const_name, $is_fully_qualified, Context $context)
    {
        $fq_const_name = null;

        $aliased_constants = $this->getAliasedConstants();

        if (isset($aliased_constants[$const_name])) {
            $fq_const_name = $aliased_constants[$const_name];
        } elseif ($is_fully_qualified) {
            $fq_const_name = $const_name;
        } elseif (strpos($const_name, '\\')) {
            $fq_const_name = ClassLikeChecker::getFQCLNFromString($const_name, $this);
        }

        if ($fq_const_name) {
            $const_name_parts = explode('\\', $fq_const_name);
            $const_name = array_pop($const_name_parts);
            $namespace_name = implode('\\', $const_name_parts);
            $namespace_constants = NamespaceChecker::getConstantsForNamespace(
                $namespace_name,
                \ReflectionProperty::IS_PUBLIC
            );

            if (isset($namespace_constants[$const_name])) {
                return $namespace_constants[$const_name];
            }
        }

        if (isset($context->vars_in_scope[$const_name])) {
            return $context->vars_in_scope[$const_name];
        }

        $predefined_constants = Config::getInstance()->getPredefinedConstants();

        if (isset($predefined_constants[$fq_const_name ?: $const_name])) {
            return ClassLikeChecker::getTypeFromValue($predefined_constants[$fq_const_name ?: $const_name]);
        }

        return null;
    }

    /**
     * @param   string      $const_name
     * @param   Type\Union  $const_type
     * @param   Context     $context
     * @return  void
     */
    public function setConstType($const_name, Type\Union $const_type, Context $context)
    {
        if ($this->source instanceof NamespaceChecker) {
            $this->source->setConstType($const_name, $const_type);
        } else {
            $context->vars_in_scope[$const_name] = $const_type;
            self::$user_constants[$this->getFilePath()][$const_name] = $const_type;
        }

    }

    /**
     * @param  PhpParser\Node\Stmt\Return_ $stmt
     * @param  Context                     $context
     * @return false|null
     */
    protected function analyzeReturn(PhpParser\Node\Stmt\Return_ $stmt, Context $context)
    {
        $type_in_comments = CommentChecker::getTypeFromComment(
            (string) $stmt->getDocComment(),
            $context,
            $this->source
        );

        if ($stmt->expr) {
            if (ExpressionChecker::analyze($this, $stmt->expr, $context) === false) {
                return false;
            }

            if ($type_in_comments) {
                $stmt->inferredType = $type_in_comments;
            } elseif (isset($stmt->expr->inferredType)) {
                $stmt->inferredType = $stmt->expr->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $stmt->inferredType = Type::getVoid();
        }

        if ($this->source instanceof FunctionLikeChecker) {
            $this->source->addReturnTypes($stmt->expr ? (string) $stmt->inferredType : '', $context);
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Throw_  $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    protected function analyzeThrow(PhpParser\Node\Stmt\Throw_ $stmt, Context $context)
    {
        return ExpressionChecker::analyze($this, $stmt->expr, $context);
    }

    /**
     * @param  string $var_name
     * @param  int    $line_number
     * @return void
     */
    public function registerVariable($var_name, $line_number)
    {
        if (!isset($this->all_vars[$var_name])) {
            $this->all_vars[$var_name] = $line_number;
        }
    }

    /**
     * @param  PhpParser\Node\Expr $dim
     * @return Type\Union
     */
    protected static function getArrayTypeFromDim(PhpParser\Node\Expr $dim)
    {
        if ($dim) {
            if (isset($dim->inferredType)) {
                return $dim->inferredType;
            } else {
                return new Type\Union([Type::getInt()->types['int'], Type::getString()->types['string']]);
            }
        } else {
            return Type::getInt();
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Include_ $stmt
     * @param  Context                      $context
     * @return false|null
     */
    public function analyzeInclude(PhpParser\Node\Expr\Include_ $stmt, Context $context)
    {
        $config = Config::getInstance();

        if (!$config->allow_includes) {
            throw new FileIncludeException('File includes are not allowed per your Psalm config - check the allowFileIncludes flag.');
        }

        if (ExpressionChecker::analyze($this, $stmt->expr, $context) === false) {
            return false;
        }

        $path_to_file = null;

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = $stmt->expr->value;

            // attempts to resolve using get_include_path dirs
            $include_path = self::resolveIncludePath($path_to_file, dirname($this->getCheckedFileName()));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if ($path_to_file[0] !== '/') {
                $path_to_file = getcwd() . '/' . $path_to_file;
            }
        } else {
            $path_to_file = self::getPathTo($stmt->expr, $this->getFileName());
        }

        if ($path_to_file) {
            $reduce_pattern = '/\/[^\/]+\/\.\.\//';

            while (preg_match($reduce_pattern, $path_to_file)) {
                $path_to_file = preg_replace($reduce_pattern, '/', $path_to_file);
            }

            // if the file is already included, we can't check much more
            if (in_array($path_to_file, get_included_files())) {
                return null;
            }

            $current_file_checker = $this->getFileChecker();

            if ($this->getFileChecker()->fileExists($path_to_file)) {
                $include_stmts = FileChecker::getStatementsForFile($path_to_file);
                $include_file_checker = new FileChecker(
                    $path_to_file,
                    $current_file_checker->project_checker,
                    $include_stmts
                );
                $include_file_checker->setFileName($this->getFileName(), $this->getFilePath());
                $include_file_checker->visit($context);
                $include_file_checker->analyze();
                return null;
            }
        }

        $context->check_classes = false;
        $context->check_variables = false;
        $context->check_functions = false;
        return null;
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @param  string              $file_name
     * @return string|null
     * @psalm-suppress MixedAssignment
     */
    protected static function getPathTo(PhpParser\Node\Expr $stmt, $file_name)
    {
        if ($file_name[0] !== '/') {
            $file_name = getcwd() . '/' . $file_name;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return $stmt->value;
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $left_string = self::getPathTo($stmt->left, $file_name);
            $right_string = self::getPathTo($stmt->right, $file_name);

            if ($left_string && $right_string) {
                return $left_string . $right_string;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name &&
            $stmt->name->parts === ['dirname']
        ) {
            if ($stmt->args) {
                $evaled_path = self::getPathTo($stmt->args[0]->value, $file_name);

                if (!$evaled_path) {
                    return null;
                }

                return dirname($evaled_path);
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch && $stmt->name instanceof PhpParser\Node\Name) {
            $const_name = implode('', $stmt->name->parts);

            if (defined($const_name)) {
                $constant_value = constant($const_name);

                if (is_string($constant_value)) {
                    return $constant_value;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir) {
            return dirname($file_name);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File) {
            return $file_name;
        }

        return null;
    }

    /**
     * @return string|null
     */

    /**
     * @param   string  $file_name
     * @param   string  $current_directory
     * @return  string|null
     */
    protected static function resolveIncludePath($file_name, $current_directory)
    {
        if (!$current_directory) {
            return $file_name;
        }

        $paths = PATH_SEPARATOR == ':'
            ? preg_split('#(?<!phar):#', get_include_path())
            : explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $prefix) {
            $ds = substr($prefix, -1) == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;

            if ($prefix === '.') {
                $prefix = $current_directory;
            }

            $file = $prefix . $ds . $file_name;

            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * The first appearance of the variable in this set of statements being evaluated
     *
     * @param  string  $var_name
     * @return int|null
     */
    public function getFirstAppearance($var_name)
    {
        return isset($this->all_vars[$var_name]) ? $this->all_vars[$var_name] : null;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$user_constants = [];

        ExpressionChecker::clearCache();
    }
}
