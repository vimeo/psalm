<?php
namespace Psalm\Checker\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;

class ConstFetchChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\ConstFetch  $stmt
     * @param   Context                         $context
     *
     * @return  void
     */
    public static function analyze(
        StatementsChecker $statements_checker,
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
                $const_type = $statements_checker->getConstType(
                    $statements_checker,
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
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
        }
    }

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ClassConstFetch $stmt
     * @param   Context                             $context
     *
     * @return  null|false
     */
    public static function analyzeClassConst(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        if ($context->check_consts
            && $stmt->class instanceof PhpParser\Node\Name
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            $first_part_lc = strtolower($stmt->class->parts[0]);

            if ($first_part_lc === 'self' || $first_part_lc === 'static') {
                if (!$context->self) {
                    throw new \UnexpectedValueException('$context->self cannot be null');
                }

                $fq_class_name = (string)$context->self;
            } elseif ($first_part_lc === 'parent') {
                $fq_class_name = $statements_checker->getParentFQCLN();

                if ($fq_class_name === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }
            } else {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker->getAliases()
                );

                if (!$context->inside_class_exists || $stmt->name->name !== 'class') {
                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
                    }
                }
            }

            if ($stmt->name->name === 'class') {
                $stmt->inferredType = Type::getClassString($fq_class_name);

                return null;
            }

            $project_checker = $statements_checker->getFileChecker()->project_checker;
            $codebase = $project_checker->codebase;

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classOrInterfaceExists($fq_class_name)) {
                $stmt->inferredType = Type::getMixed();

                return null;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($fq_class_name === $context->self
                || (
                    $statements_checker->getSource()->getSource() instanceof TraitChecker &&
                    $fq_class_name === $statements_checker->getSource()->getFQCLN()
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
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return false;
            }

            if ($context->calling_method_id) {
                \Psalm\Provider\FileReferenceProvider::addReferenceToClassMethod(
                    $context->calling_method_id,
                    strtolower($fq_class_name) . '::' . $stmt->name->name
                );
            }

            $class_const_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            if (isset($class_const_storage->deprecated_constants[$stmt->name->name])) {
                if (IssueBuffer::accepts(
                    new DeprecatedConstant(
                        'Constant ' . $const_id . ' is deprecated',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if (isset($class_constants[$stmt->name->name]) && $first_part_lc !== 'static') {
                $stmt->inferredType = clone $class_constants[$stmt->name->name];
            } else {
                $stmt->inferredType = Type::getMixed();
            }

            return null;
        }

        $stmt->inferredType = Type::getMixed();

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->class, $context) === false) {
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

            $type = ClassLikeChecker::getTypeFromValue($predefined_constants[$fq_const_name ?: $const_name]);
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
