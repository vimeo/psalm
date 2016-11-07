<?php
namespace Psalm\Example\Template;

use Psalm;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Context;
use Psalm\Type;
use PhpParser;

class TemplateChecker extends Psalm\Checker\FileChecker
{
    const THIS_CLASS = 'Psalm\\Example\\Template\\Base';

    /**
     * @param   bool            $check_classes
     * @param   bool            $check_class_statements
     * @param   Context|null    $file_context
     * @param   bool            $cache
     * @return  false|null
     */
    public function check(
        $check_classes = true,
        $check_class_statements = true,
        Context $file_context = null,
        $cache = true
    ) {
        $stmts = $this->getStatements();

        if (empty($stmts)) {
            return null;
        }

        $first_stmt = $stmts[0];

        $this_params = null;

        if (($first_stmt instanceof PhpParser\Node\Stmt\Nop) && ($doc_comment = $first_stmt->getDocComment())) {
            $comment_block = CommentChecker::parseDocComment(trim($doc_comment->getText()));

            if (isset($comment_block['specials']['variablesfrom'])) {
                $variables_from = trim($comment_block['specials']['variablesfrom'][0]);

                $first_line_regex = '/([A-Za-z\\\0-9]+::[a-z_A-Z]+)?/';

                $matches = [];

                if (!preg_match($first_line_regex, $variables_from, $matches)) {
                    throw new \InvalidArgumentException('Could not interpret doc comment correctly');
                }

                $this_params = $this->checkMethod($matches[1]);

                if ($this_params === false) {
                    return false;
                }

                $this_params->vars_in_scope['$this'] = new Type\Union([new Type\Atomic(self::THIS_CLASS)]);
            }
        }

        if (!$this_params) {
            $this_params = new Context($this->short_file_name);
            $this_params->check_variables = false;
            $this_params->self = self::THIS_CLASS;
        }

        $this->checkWithViewClass($this_params);
        return null;
    }

    /**
     * @param   string $method_id
     * @return  bool|Context
     */
    private function checkMethod($method_id)
    {
        $class = explode('::', $method_id)[0];

        if (ClassLikeChecker::checkFullQualifiedClassOrInterface($class, $this->short_file_name, 1, []) === false) {
            return false;
        }

        $this_context = new Context($this->short_file_name);
        $this_context->self = $class;
        $this_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic($class)]);

        $constructor_id = $class . '::__construct';

        // this is necessary to enable deep checks
        ClassChecker::setThisClass($class);

        // check the constructor
        $constructor_method_checker = ClassChecker::getMethodChecker($constructor_id);

        if ($constructor_method_checker->check($this_context) === false) {
            ClassChecker::setThisClass(null);
            return false;
        }

        $this_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic($class)]);

        // check the actual method
        $method_checker = ClassChecker::getMethodChecker($method_id);
        if ($method_checker->check($this_context) === false) {
            ClassChecker::setThisClass(null);
            return false;
        }

        $view_context = new Context($this->short_file_name);
        $view_context->self = self::THIS_CLASS;

        // add all $this-> vars to scope
        foreach ($this_context->vars_possibly_in_scope as $var => $type) {
            $view_context->vars_in_scope[str_replace('$this->', '$', $var)] = Type::getMixed();
        }

        foreach ($this_context->vars_in_scope as $var => $type) {
            $view_context->vars_in_scope[str_replace('$this->', '$', $var)] = $type;
        }

        ClassChecker::setThisClass(null);

        return $view_context;
    }

    /**
     * @param   Context $context
     * @return  void
     */
    protected function checkWithViewClass(Context $context)
    {
        $class_name = self::THIS_CLASS;

        // check that class first
        FileChecker::getClassLikeCheckerFromClass($class_name)->check(true);

        $stmts = $this->getStatements();

        $class_method = new PhpParser\Node\Stmt\ClassMethod($class_name, ['stmts' => $stmts]);

        $class = new PhpParser\Node\Stmt\Class_($class_name);

        $class_checker = new ClassChecker($class, $this, $class_name);

        (new MethodChecker($class_method, $class_checker))->check($context);
    }
}
