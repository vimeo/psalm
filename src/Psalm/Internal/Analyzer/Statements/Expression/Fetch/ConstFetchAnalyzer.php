<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;

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
                $stmt->inferredType = Type::getNull();
                break;

            case 'false':
                // false is a subtype of bool
                $stmt->inferredType = Type::getFalse();
                break;

            case 'true':
                $stmt->inferredType = Type::getTrue();
                break;

            case 'stdin':
                $stmt->inferredType = Type::getResource();
                break;

            default:
                $const_type = $statements_analyzer->getConstType(
                    $statements_analyzer,
                    $const_name,
                    $stmt->name instanceof PhpParser\Node\Name\FullyQualified,
                    $context
                );

                if ($const_type) {
                    $stmt->inferredType = clone $const_type;
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
     * @param   StatementsAnalyzer                   $statements_analyzer
     * @param   PhpParser\Node\Expr\ClassConstFetch $stmt
     * @param   Context                             $context
     *
     * @return  null|false
     */
    public static function analyzeClassConst(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if ($context->check_consts
                || ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class')
            ) {
                $first_part_lc = strtolower($stmt->class->parts[0]);

                if ($first_part_lc === 'self' || $first_part_lc === 'static') {
                    if (!$context->self) {
                        throw new \UnexpectedValueException('$context->self cannot be null');
                    }

                    $fq_class_name = (string)$context->self;
                } elseif ($first_part_lc === 'parent') {
                    $fq_class_name = $statements_analyzer->getParentFQCLN();

                    if ($fq_class_name === null) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot check property fetch on parent as this class does not extend another',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }
                } else {
                    $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $statements_analyzer->getAliases()
                    );

                    if ($stmt->name instanceof PhpParser\Node\Identifier) {
                        if (!$context->inside_class_exists || $stmt->name->name !== 'class') {
                            if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                $statements_analyzer,
                                $fq_class_name,
                                new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                                $statements_analyzer->getSuppressedIssues(),
                                false
                            ) === false) {
                                return false;
                            }
                        }
                    }
                }

                if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
                    $stmt->inferredType = Type::getLiteralClassString($fq_class_name);

                    return null;
                }

                // if we're ignoring that the class doesn't exist, exit anyway
                if (!$codebase->classOrInterfaceExists($fq_class_name)) {
                    $stmt->inferredType = Type::getMixed();

                    return null;
                }

                if ($codebase->server_mode) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->class,
                        $fq_class_name
                    );
                }

                if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                    return null;
                }

                $const_id = $fq_class_name . '::' . $stmt->name;

                if ($codebase->server_mode) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->name,
                        $const_id
                    );
                }

                if ($fq_class_name === $context->self
                    || (
                        $statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer &&
                        $fq_class_name === $statements_analyzer->getSource()->getFQCLN()
                    )
                ) {
                    $class_visibility = \ReflectionProperty::IS_PRIVATE;
                } elseif ($context->self &&
                    $codebase->classExtends($context->self, $fq_class_name)
                ) {
                    $class_visibility = \ReflectionProperty::IS_PROTECTED;
                } else {
                    $class_visibility = \ReflectionProperty::IS_PUBLIC;
                }

                $class_constants = $codebase->classlikes->getConstantsForClass(
                    $fq_class_name,
                    $class_visibility
                );

                if (!isset($class_constants[$stmt->name->name]) && $first_part_lc !== 'static') {
                    $all_class_constants = [];

                    if ($fq_class_name !== $context->self) {
                        $all_class_constants = $codebase->classlikes->getConstantsForClass(
                            $fq_class_name,
                            \ReflectionProperty::IS_PRIVATE
                        );
                    }

                    if ($all_class_constants && isset($all_class_constants[$stmt->name->name])) {
                        if (IssueBuffer::accepts(
                            new InaccessibleClassConstant(
                                'Constant ' . $const_id . ' is not visible in this context',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new UndefinedConstant(
                                'Constant ' . $const_id . ' is not defined',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    return false;
                }

                if ($context->calling_method_id) {
                    $codebase->file_reference_provider->addReferenceToClassMethod(
                        $context->calling_method_id,
                        strtolower($fq_class_name) . '::' . $stmt->name->name
                    );
                }

                $class_const_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                if (isset($class_const_storage->deprecated_constants[$stmt->name->name])) {
                    if (IssueBuffer::accepts(
                        new DeprecatedConstant(
                            'Constant ' . $const_id . ' is deprecated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if (isset($class_constants[$stmt->name->name]) && $first_part_lc !== 'static') {
                    $stmt->inferredType = clone $class_constants[$stmt->name->name];
                    $context->vars_in_scope[$const_id] = $stmt->inferredType;
                } else {
                    $stmt->inferredType = Type::getMixed();
                }

                return null;
            }
        } elseif ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);
            $lhs_type = $stmt->class->inferredType;

            $class_string_types = [];

            $has_mixed_or_object = false;

            if ($lhs_type) {
                foreach ($lhs_type->getTypes() as $lhs_atomic_type) {
                    if ($lhs_atomic_type instanceof Type\Atomic\TNamedObject) {
                        $class_string_types[] = new Type\Atomic\TClassString(
                            $lhs_atomic_type->value,
                            clone $lhs_atomic_type
                        );
                    } elseif ($lhs_atomic_type instanceof Type\Atomic\TObject
                        || $lhs_atomic_type instanceof Type\Atomic\TMixed
                    ) {
                        $has_mixed_or_object = true;
                    }
                }
            }

            if ($has_mixed_or_object) {
                $stmt->inferredType = new Type\Union([new Type\Atomic\TClassString()]);
            } elseif ($class_string_types) {
                $stmt->inferredType = new Type\Union($class_string_types);
            } else {
                $stmt->inferredType = Type::getMixed();
            }

            return;
        }

        $stmt->inferredType = Type::getMixed();

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context) === false) {
                return false;
            }
        }

        return null;
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

        $predefined_constants = $codebase->config->getPredefinedConstants();

        if (isset($predefined_constants[$fq_const_name ?: $const_name])) {
            switch ($fq_const_name ?: $const_name) {
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

            $type = ClassLikeAnalyzer::getTypeFromValue($predefined_constants[$fq_const_name ?: $const_name]);
            return $type;
        }

        $stubbed_const_type = $codebase->getStubbedConstantType(
            $fq_const_name ?: $const_name
        );

        if ($stubbed_const_type) {
            return $stubbed_const_type;
        }

        return null;
    }
}
