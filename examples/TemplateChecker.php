<?php
namespace Psalm\Examples\Template;

use PhpParser;
use Psalm;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Type;

class TemplateAnalyzer extends Psalm\Internal\Analyzer\FileAnalyzer
{
    const VIEW_CLASS = 'Your\\View\\Class';

    public function analyze(Context $context = null, $update_docblocks = false, Context $global_context = null)
    {
        $codebase = $this->project_checker->getCodebase();
        $stmts = $codebase->getStatementsForFile($this->file_path);

        if (empty($stmts)) {
            return;
        }

        $first_stmt = $stmts[0];

        $this_params = null;

        if (($first_stmt instanceof PhpParser\Node\Stmt\Nop) && ($doc_comment = $first_stmt->getDocComment())) {
            $comment_block = DocComment::parse(trim($doc_comment->getText()));

            if (isset($comment_block['specials']['variablesfrom'])) {
                $variables_from = trim($comment_block['specials']['variablesfrom'][0]);

                $first_line_regex = '/([A-Za-z\\\0-9]+::[a-z_A-Z]+)(\s+weak)?/';

                $matches = [];

                if (!preg_match($first_line_regex, $variables_from, $matches)) {
                    throw new \InvalidArgumentException('Could not interpret doc comment correctly');
                }

                /** @psalm-suppress MixedArgument */
                $this_params = $this->checkMethod($matches[1], $first_stmt, $codebase);

                if ($this_params === false) {
                    return;
                }

                $this_params->vars_in_scope['$this'] = new Type\Union([
                    new Type\Atomic\TNamedObject(self::VIEW_CLASS),
                ]);
            }
        }

        if (!$this_params) {
            $this_params = new Context();
            $this_params->check_variables = false;
            $this_params->self = self::VIEW_CLASS;
            $this_params->vars_in_scope['$this'] = new Type\Union([
                new Type\Atomic\TNamedObject(self::VIEW_CLASS),
            ]);
        }

        $this->checkWithViewClass($this_params, $stmts);
    }

    /**
     * @param  string         $method_id
     * @param  PhpParser\Node $stmt
     *
     * @return Context|false
     */
    private function checkMethod($method_id, PhpParser\Node $stmt, Codebase $codebase)
    {
        $class = explode('::', $method_id)[0];

        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $this,
            $class,
            new CodeLocation($this, $stmt),
            [],
            true
        ) === false
        ) {
            return false;
        }

        $this_context = new Context();
        $this_context->self = $class;
        $this_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic\TNamedObject($class)]);

        $constructor_id = $class . '::__construct';

        $this->project_checker->getMethodMutations($constructor_id, $this_context);

        $this_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic\TNamedObject($class)]);

        // check the actual method
        $this->project_checker->getMethodMutations($method_id, $this_context);

        $view_context = new Context();
        $view_context->self = self::VIEW_CLASS;

        // add all $this-> vars to scope
        foreach ($this_context->vars_possibly_in_scope as $var => $_) {
            $view_context->vars_in_scope[str_replace('$this->', '$', $var)] = Type::getMixed();
        }

        foreach ($this_context->vars_in_scope as $var => $type) {
            $view_context->vars_in_scope[str_replace('$this->', '$', $var)] = $type;
        }

        return $view_context;
    }

    /**
     * @param  Context $context
     * @param  array<PhpParser\Node\Stmt> $stmts
     *
     * @return void
     */
    protected function checkWithViewClass(Context $context, array $stmts)
    {
        $pseudo_method_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } else {
                $pseudo_method_stmts[] = $stmt;
            }
        }

        $pseudo_method_name = preg_replace('/[^a-zA-Z0-9_]+/', '_', $this->file_name);

        $class_method = new PhpParser\Node\Stmt\ClassMethod($pseudo_method_name, ['stmts' => []]);

        $class = new PhpParser\Node\Stmt\Class_(self::VIEW_CLASS);

        $class_checker = new ClassAnalyzer($class, $this, self::VIEW_CLASS);

        $view_method_checker = new MethodAnalyzer($class_method, $class_checker);

        if (!$context->check_variables) {
            $view_method_checker->addSuppressedIssue('UndefinedVariable');
        }

        $statements_source = new StatementsAnalyzer($view_method_checker);

        $statements_source->analyze($pseudo_method_stmts, $context);
    }
}
