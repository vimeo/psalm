<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;
use function array_key_exists;
use function implode;
use function strtolower;

/**
 * @internal
 */
class ConstFetchAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\ConstFetch  $stmt
     * @param   Context                         $context
     *
     * @return  void
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ConstFetch $stmt,
        Context $context
    ) {
        $const_name = implode('\\', $stmt->name->parts);

        switch (strtolower($const_name)) {
            case 'null':
                $statements_analyzer->node_data->setType($stmt, Type::getNull());
                break;

            case 'false':
                // false is a subtype of bool
                $statements_analyzer->node_data->setType($stmt, Type::getFalse());
                break;

            case 'true':
                $statements_analyzer->node_data->setType($stmt, Type::getTrue());
                break;

            case 'stdin':
                $statements_analyzer->node_data->setType($stmt, Type::getResource());
                break;

            default:
                $const_type = $statements_analyzer->getConstType(
                    $const_name,
                    $stmt->name instanceof PhpParser\Node\Name\FullyQualified,
                    $context
                );

                if ($const_type) {
                    $statements_analyzer->node_data->setType($stmt, clone $const_type);
                } elseif ($context->check_consts) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Const ' . $const_name . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
        }
    }

    /**
     * @param  Codebase $codebase
     * @param  ?string  $fq_const_name
     * @param  string   $const_name
     *
     * @return Type\Union|null
     */
    public static function getGlobalConstType(
        Codebase $codebase,
        $fq_const_name,
        $const_name
    ) {
        if ($const_name === 'STDERR'
            || $const_name === 'STDOUT'
            || $const_name === 'STDIN'
        ) {
            return Type::getResource();
        }

        if ($fq_const_name) {
            $stubbed_const_type = $codebase->getStubbedConstantType(
                $fq_const_name
            );

            if ($stubbed_const_type) {
                return $stubbed_const_type;
            }
        }

        $stubbed_const_type = $codebase->getStubbedConstantType(
            $const_name
        );

        if ($stubbed_const_type) {
            return $stubbed_const_type;
        }

        $predefined_constants = $codebase->config->getPredefinedConstants();

        if (($fq_const_name && array_key_exists($fq_const_name, $predefined_constants))
            || array_key_exists($const_name, $predefined_constants)
        ) {
            switch ($const_name) {
                case 'PHP_VERSION':
                case 'DIRECTORY_SEPARATOR':
                case 'PATH_SEPARATOR':
                case 'PEAR_EXTENSION_DIR':
                case 'PEAR_INSTALL_DIR':
                case 'PHP_BINARY':
                case 'PHP_BINDIR':
                case 'PHP_CONFIG_FILE_PATH':
                case 'PHP_CONFIG_FILE_SCAN_DIR':
                case 'PHP_DATADIR':
                case 'PHP_EOL':
                case 'PHP_EXTENSION_DIR':
                case 'PHP_EXTRA_VERSION':
                case 'PHP_LIBDIR':
                case 'PHP_LOCALSTATEDIR':
                case 'PHP_MANDIR':
                case 'PHP_OS':
                case 'PHP_OS_FAMILY':
                case 'PHP_PREFIX':
                case 'PHP_SAPI':
                case 'PHP_SYSCONFDIR':
                    return Type::getString();

                case 'PHP_MAJOR_VERSION':
                case 'PHP_MINOR_VERSION':
                case 'PHP_RELEASE_VERSION':
                case 'PHP_DEBUG':
                case 'PHP_FLOAT_DIG':
                case 'PHP_INT_MAX':
                case 'PHP_INT_MIN':
                case 'PHP_INT_SIZE':
                case 'PHP_MAXPATHLEN':
                case 'PHP_VERSION_ID':
                case 'PHP_ZTS':
                    return Type::getInt();

                case 'PHP_FLOAT_EPSILON':
                case 'PHP_FLOAT_MAX':
                case 'PHP_FLOAT_MIN':
                    return Type::getFloat();
            }

            if ($fq_const_name && array_key_exists($fq_const_name, $predefined_constants)) {
                return ClassLikeAnalyzer::getTypeFromValue($predefined_constants[$fq_const_name]);
            }

            return ClassLikeAnalyzer::getTypeFromValue($predefined_constants[$const_name]);
        }

        return null;
    }
}
