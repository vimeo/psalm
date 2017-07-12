<?php
namespace Psalm\Visitor;

use PhpParser;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\Statements\ExpressionChecker;

class DependencyFinderVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /**
     * @var array<string, string>
     */
    protected $aliased_uses = [];

    /**
     * @var array<string, string>
     */
    protected $aliased_functions = [];

    /**
     * @var array<string, string>
     */
    protected $aliased_constants = [];

    /**
     * @var array<string, string>
     */
    protected $file_aliased_uses = [];

    /**
     * @var array<string, string>
     */
    protected $file_aliased_functions = [];

    /**
     * @var array<string, string>
     */
    protected $file_aliased_constants = [];

    protected $in_classlike = false;


    protected $namespace_name = null;

    /** @var ProjectChecker */
    protected $project_checker;

    /**
     * @param string|null $this_class_name
     */
    public function __construct(ProjectChecker $project_checker)
    {
        $this->project_checker = $project_checker;
    }

    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->namespace_name = $node->name ? implode('\\', $node->name->parts) : '';
            $this->file_aliased_uses = $this->aliased_uses;
            $this->file_aliased_functions = $this->aliased_functions;
            $this->file_aliased_constants = $this->aliased_constants;
        } elseif ($node instanceof PhpParser\Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $use_path = implode('\\', $use->name->parts);

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliased_functions[strtolower($use->alias)] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliased_constants[$use->alias] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliased_uses[strtolower($use->alias)] = $use_path;
                        break;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\GroupUse) {
            $use_prefix = implode('\\', $node->prefix->parts);

            foreach ($node->uses as $use) {
                $use_path = $use_prefix . '\\' . implode('\\', $use->name->parts);

                switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type) {
                    case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                        $this->aliased_functions[strtolower($use->alias)] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                        $this->aliased_constants[$use->alias] = $use_path;
                        break;

                    case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                        $this->aliased_uses[strtolower($use->alias)] = $use_path;
                        break;
                }
            }
        } elseif ($node instanceof PhpParser\Node\Stmt\ClassLike && $node->name) {
            $fqcln = ($this->namespace_name ? $this->namespace_name . '\\' : '') . $node->name;
            var_dump('Class: ' . $fqcln);

            if ($node instanceof PhpParser\Node\Stmt\Class_) {
                if ($node->extends) {
                    $parent_fqcln = $this->getFQCLN($node->extends->parts);
                    var_dump('Class parent: ' . $parent_fqcln);
                }
                
                foreach ($node->implements as $interface) {
                    $interface_fqcln = $this->getFQCLN($interface->parts);
                    var_dump('Class implements: ' . $interface_fqcln);
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Interface_) {
                foreach ($node->extends as $interface) {
                    $interface_fqcln = $this->getFQCLN($interface->parts);
                    var_dump('Interface extends: ' . $interface_fqcln);
                }
            }
        } elseif (($node instanceof PhpParser\Node\Expr\New_
                || $node instanceof PhpParser\Node\Expr\Instanceof_
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                || $node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\StaticCall)
            && $node->class instanceof PhpParser\Node\Name
        ) {
            var_dump($this->getFQCLN($node->class->parts));
        } elseif ($node instanceof PhpParser\Node\Stmt\TryCatch) {
            foreach ($stmt->catches as $catch) {
                foreach ($catch->types as $catch_type) {
                    var_dump($this->getFQCLN($catch_type->parts));
                }
            }
        } elseif ($node instanceof PhpParser\Node\FunctionLike) {
            $return_type = $node->getReturnType();

            if ($return_type) {
                if ($return_type instanceof PhpParser\Node\Name) {
                    var_dump($this->getFQCLN($return_type->parts));
                } elseif ($return_type instanceof PhpParser\Node\NullableType
                    && $return_type->type instanceof PhpParser\Node\Name
                ) {
                    var_dump($this->getFQCLN($return_type->type->parts));
                }
            }

            $params = $node->getParams();

            foreach ($params as $param) {
                if ($param->type instanceof PhpParser\Node\Name) {
                    var_dump($this->getFQCLN($param->type->parts));
                }
            }
        }
    }

    public function getFQCLN(array $class_parts)
    {
        $first_part = $class_parts[0];

        if (in_array($first_part, ['self', 'static', 'parent'], true)) {
            return null;
        }

        if (isset($this->aliased_uses[strtolower($first_part)])) {
            $stub = $this->aliased_uses[strtolower($first_part)];

            if (count($class_parts) > 1) {
                array_shift($class_parts);
                return $stub . '\\' . implode('\\', $class_parts);
            }

            return $stub;
        }

        return ($this->namespace_name ? $this->namespace_name . '\\' : '') . implode('\\', $class_parts);
    }

    public function leaveNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
            $this->namespace_name = null;
            $this->aliased_uses = $this->file_aliased_uses;
            $this->aliased_functions = $this->file_aliased_functions;
            $this->aliased_constants = $this->file_aliased_constants;
        }
    }
}
