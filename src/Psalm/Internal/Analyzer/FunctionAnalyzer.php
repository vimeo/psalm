<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\Interner;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use UnexpectedValueException;

use function is_string;

/**
 * @internal
 * @extends FunctionLikeAnalyzer<PhpParser\Node\Stmt\Function_>
 */
final class FunctionAnalyzer extends FunctionLikeAnalyzer
{
    use UnserializeMemoryUsageSuppressionTrait;

    /** function id as declared */
    private readonly int $function_id;

    /**
     * @psalm-mutation-free
     */
    public function __construct(PhpParser\Node\Stmt\Function_ $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $file_storage_provider = $codebase->file_storage_provider;

        $file_storage = $file_storage_provider->get($source->getFilePath());

        $function_id = $this->function_id = self::buildFunctionId($source->getNamespace(), $function->name->name);

        if (!isset($file_storage->functions[$function_id])) {
            throw new UnexpectedValueException(
                'Function ' . Interner::str($function_id) . ' should be defined in ' . $source->getFilePath(),
            );
        }

        $storage = $file_storage->functions[$function_id];

        parent::__construct($function, $source, $storage);
    }

    /**
     * @return int function id as declared
     * @psalm-pure
     */
    private static function buildFunctionId(?int $namespace, string $function_name): int
    {
        $namespace_str = $namespace !== null ? Interner::str($namespace) : '';
        return Interner::intern(($namespace_str !== '' ? $namespace_str . '\\' : '') . $function_name);
    }

    /**
     * @return int function id as declared
     * @psalm-mutation-free
     */
    public function getFunctionId(): int
    {
        return $this->function_id;
    }

    public static function analyzeStatement(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Function_ $stmt,
        Context $context,
    ): void {
        foreach ($stmt->stmts as $function_stmt) {
            if ($function_stmt instanceof PhpParser\Node\Stmt\Global_) {
                foreach ($function_stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name)) {
                            $var_id = '$' . $var->name;

                            // registers variable in global context
                            $context->hasVariable($var_id);
                        }
                    }
                }
            } elseif (!$function_stmt instanceof PhpParser\Node\Stmt\Nop) {
                break;
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->register_stub_files
            && !$codebase->register_autoload_files
        ) {
            $fq_function_name = self::buildFunctionId($statements_analyzer->getNamespace(), $stmt->name->name);

            $function_context = new Context($context->self);
            $function_context->strict_types = $context->strict_types;
            $config = Config::getInstance();
            $function_context->collect_exceptions = $config->check_for_throws_docblock;

            if ($function_analyzer = $statements_analyzer->getFunctionAnalyzer($fq_function_name)) {
                $function_analyzer->analyze(
                    $function_context,
                    $statements_analyzer->node_data,
                    $context,
                );

                if ($config->reportIssueInFile('InvalidReturnType', $statements_analyzer->getFilePath())) {
                    $function_storage = $codebase->functions->getStorage(
                        $statements_analyzer,
                        $function_analyzer->getFunctionId(),
                    );

                    $return_type = $function_storage->return_type;
                    $return_type_location = $function_storage->return_type_location;

                    $function_analyzer->verifyReturnType(
                        $stmt->getStmts(),
                        $statements_analyzer,
                        $return_type,
                        $statements_analyzer->getFQCLN(),
                        $return_type_location,
                        $function_context->has_returned,
                    );
                }
            }
        }
    }
}
