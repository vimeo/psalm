<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\InternalProperty;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyInvalidPropertyFetch;
use Psalm\Issue\PossiblyNullPropertyFetch;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;

/**
 * @internal
 */
class PropertyFetchAnalyzer
{
    /**
     * @param   StatementsAnalyzer                   $statements_analyzer
     * @param   PhpParser\Node\Expr\PropertyFetch   $stmt
     * @param   Context                             $context
     *
     * @return  false|null
     */
    public static function analyzeInstance(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context
    ) {
        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                return false;
            }
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        if ($stmt->name instanceof PhpParser\Node\Identifier) {
            $prop_name = $stmt->name->name;
        } elseif (isset($stmt->name->inferredType)
            && $stmt->name->inferredType->isSingleStringLiteral()
        ) {
            $prop_name = $stmt->name->inferredType->getSingleStringLiteral()->value;
        } else {
            $prop_name = null;
        }

        $codebase = $statements_analyzer->getCodebase();

        $stmt_var_id = ExpressionAnalyzer::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_id = ExpressionAnalyzer::getArrayVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $stmt_var_type = null;
        $stmt->inferredType = null;

        if ($var_id && $context->hasVariable($var_id, $statements_analyzer)) {
            // we don't need to check anything
            $stmt->inferredType = $context->vars_in_scope[$var_id];

            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());

            if ($codebase->server_mode
                && (!$context->collect_initializations
                    && !$context->collect_mutations)
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    (string) $stmt->inferredType
                );
            }

            if (isset($stmt->var->inferredType)
                && $stmt->var->inferredType->hasObjectType()
                && $stmt->name instanceof PhpParser\Node\Identifier
            ) {
                // log the appearance
                foreach ($stmt->var->inferredType->getTypes() as $lhs_type_part) {
                    if ($lhs_type_part instanceof TNamedObject) {
                        if (!$codebase->classExists($lhs_type_part->value)) {
                            continue;
                        }

                        $property_id = $lhs_type_part->value . '::$' . $stmt->name->name;

                        $codebase->properties->propertyExists(
                            $property_id,
                            $context->calling_method_id,
                            $context->collect_references
                                ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                                : null
                        );

                        if ($codebase->server_mode) {
                            $codebase->analyzer->addNodeReference(
                                $statements_analyzer->getFilePath(),
                                $stmt->name,
                                $property_id
                            );
                        }
                    }
                }
            }

            return null;
        }

        if ($stmt_var_id && $context->hasVariable($stmt_var_id, $statements_analyzer)) {
            $stmt_var_type = $context->vars_in_scope[$stmt_var_id];
        } elseif (isset($stmt->var->inferredType)) {
            $stmt_var_type = $stmt->var->inferredType;
        }

        if (!$stmt_var_type) {
            return null;
        }

        if ($stmt_var_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullPropertyFetch(
                    'Cannot get property on null variable ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($stmt_var_type->isEmpty()) {
            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on empty var ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($stmt_var_type->hasMixed()) {
            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());

            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on mixed var ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            if ($stmt_var_type->hasMixed()) {
                $stmt->inferredType = Type::getMixed();

                if ($codebase->server_mode
                    && (!$context->collect_initializations
                        && !$context->collect_mutations)
                ) {
                    $codebase->analyzer->addNodeType(
                        $statements_analyzer->getFilePath(),
                        $stmt->name,
                        (string) $stmt->inferredType
                    );
                }

                return null;
            }
        }

        $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getRootFilePath());

        if ($stmt_var_type->isNullable() && !$stmt_var_type->ignore_nullable_issues && !$context->inside_isset) {
            if (IssueBuffer::accepts(
                new PossiblyNullPropertyFetch(
                    'Cannot get property on possibly null variable ' . $stmt_var_id . ' of type ' . $stmt_var_type,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            $stmt->inferredType = Type::getNull();
        }

        if (!$prop_name) {
            return null;
        }

        $invalid_fetch_types = [];
        $has_valid_fetch_type = false;

        foreach ($stmt_var_type->getTypes() as $lhs_type_part) {
            if ($lhs_type_part instanceof TNull) {
                continue;
            }

            if ($lhs_type_part instanceof Type\Atomic\TGenericParam) {
                $extra_types = $lhs_type_part->extra_types;

                $lhs_type_part = array_values(
                    $lhs_type_part->as->getTypes()
                )[0];

                $lhs_type_part->from_docblock = true;

                if ($lhs_type_part instanceof TNamedObject) {
                    $lhs_type_part->extra_types = $extra_types;
                }
            }

            if ($lhs_type_part instanceof Type\Atomic\TMixed) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if ($lhs_type_part instanceof Type\Atomic\TFalse && $stmt_var_type->ignore_falsable_issues) {
                continue;
            }

            if (!$lhs_type_part instanceof TNamedObject && !$lhs_type_part instanceof TObject) {
                $invalid_fetch_types[] = (string)$lhs_type_part;

                continue;
            }

            $has_valid_fetch_type = true;

            // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
            // but we don't want to throw an error
            // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
            if ($lhs_type_part instanceof TObject
                || in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement'], true)
            ) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if (ExpressionAnalyzer::isMock($lhs_type_part->value)) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            $intersection_types = $lhs_type_part->getIntersectionTypes() ?: [];

            $fq_class_name = $lhs_type_part->value;

            $override_property_visibility = false;

            if (!$codebase->classExists($lhs_type_part->value)) {
                $class_exists = false;

                if ($codebase->interfaceExists($lhs_type_part->value)) {
                    $interface_storage = $codebase->classlike_storage_provider->get($lhs_type_part->value);

                    $override_property_visibility = $interface_storage->override_property_visibility;

                    foreach ($intersection_types as $intersection_type) {
                        if ($intersection_type instanceof TNamedObject
                            && $codebase->classExists($intersection_type->value)
                        ) {
                            $fq_class_name = $intersection_type->value;
                            $class_exists = true;
                            break;
                        }
                    }

                    if (!$class_exists) {
                        if (IssueBuffer::accepts(
                            new NoInterfaceProperties(
                                'Interfaces cannot have properties',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        return null;
                    }
                }

                if (!$class_exists) {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Cannot set properties of undefined class ' . $lhs_type_part->value,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $lhs_type_part->value
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return null;
                }
            }

            $property_id = $fq_class_name . '::$' . $prop_name;

            if ($codebase->server_mode) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $property_id
                );
            }

            if ($codebase->methodExists($fq_class_name . '::__get')
                && (!$codebase->properties->propertyExists($property_id)
                    || ($stmt_var_id !== '$this'
                        && $fq_class_name !== $context->self
                        && ClassLikeAnalyzer::checkPropertyVisibility(
                            $property_id,
                            $context->self,
                            $statements_analyzer,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $statements_analyzer->getSuppressedIssues(),
                            false
                        ) !== true)
                )
            ) {
                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                if (isset($class_storage->pseudo_property_get_types['$' . $prop_name])) {
                    $stmt->inferredType = clone $class_storage->pseudo_property_get_types['$' . $prop_name];
                    continue;
                }

                $stmt->inferredType = Type::getMixed();
                /*
                 * If we have an explicit list of all allowed magic properties on the class, and we're
                 * not in that list, fall through
                 */
                if (!$class_storage->sealed_properties && !$override_property_visibility) {
                    continue;
                }
            }

            if (!$codebase->properties->propertyExists(
                $property_id,
                $context->calling_method_id,
                $context->collect_references ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null
            )
            ) {
                if ($context->inside_isset) {
                    return;
                }

                if ($stmt_var_id === '$this') {
                    if ($context->collect_mutations) {
                        return;
                    }

                    if (IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $stmt->inferredType = Type::getMixed();

                if ($var_id) {
                    $context->vars_in_scope[$var_id] = $stmt->inferredType;
                }

                return;
            }

            if (!$override_property_visibility) {
                if (ClassLikeAnalyzer::checkPropertyVisibility(
                    $property_id,
                    $context->self,
                    $statements_analyzer,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }

            $declaring_property_class = (string) $codebase->properties->getDeclaringClassForProperty($property_id);

            $declaring_class_storage = $codebase->classlike_storage_provider->get(
                $declaring_property_class
            );

            $property_storage = $declaring_class_storage->properties[$prop_name];

            if ($property_storage->deprecated) {
                if (IssueBuffer::accepts(
                    new DeprecatedProperty(
                        $property_id . ' is marked deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($property_storage->internal && $context->self) {
                $self_root = preg_replace('/^([^\\\]+).*/', '$1', $context->self);
                $declaring_root = preg_replace('/^([^\\\]+).*/', '$1', $declaring_property_class);

                if (strtolower($self_root) !== strtolower($declaring_root)) {
                    if (IssueBuffer::accepts(
                        new InternalProperty(
                            $property_id . ' is marked internal',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            $class_property_type = $property_storage->type;

            if (!$class_property_type) {
                if (IssueBuffer::accepts(
                    new MissingPropertyType(
                        'Property ' . $fq_class_name . '::$' . $prop_name
                            . ' does not have a declared type',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                $class_property_type = Type::getMixed();
            } else {
                $class_property_type = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    clone $class_property_type,
                    $declaring_property_class,
                    $declaring_property_class
                );

                if ($lhs_type_part instanceof TGenericObject) {
                    $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                    if ($class_storage->template_types) {
                        $class_template_params = [];

                        $reversed_class_template_types = array_reverse(array_keys($class_storage->template_types));

                        $provided_type_param_count = count($lhs_type_part->type_params);

                        foreach ($reversed_class_template_types as $i => $type_name) {
                            if (isset($lhs_type_part->type_params[$provided_type_param_count - 1 - $i])) {
                                $class_template_params[$type_name] =
                                    (string)$lhs_type_part->type_params[$provided_type_param_count - 1 - $i];
                            } else {
                                $class_template_params[$type_name] = 'mixed';
                            }
                        }

                        $type_tokens = Type::tokenize((string)$class_property_type);

                        foreach ($type_tokens as &$type_token) {
                            if (isset($class_template_params[$type_token])) {
                                $type_token = $class_template_params[$type_token];
                            }
                        }

                        $class_property_type = Type::parseString(implode('', $type_tokens));
                    }
                }
            }

            if (isset($stmt->inferredType)) {
                $stmt->inferredType = Type::combineUnionTypes($class_property_type, $stmt->inferredType);
            } else {
                $stmt->inferredType = $class_property_type;
            }
        }

        if ($codebase->server_mode
            && (!$context->collect_initializations
                && !$context->collect_mutations)
            && isset($stmt->inferredType)
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                (string) $stmt->inferredType
            );
        }

        if ($invalid_fetch_types) {
            $lhs_type_part = $invalid_fetch_types[0];

            if ($has_valid_fetch_type) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyFetch(
                        'Cannot fetch property on possible non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = isset($stmt->inferredType) ? $stmt->inferredType : Type::getMixed();
        }
    }

    /**
     * @param   StatementsAnalyzer                       $statements_analyzer
     * @param   PhpParser\Node\Expr\StaticPropertyFetch $stmt
     * @param   Context                                 $context
     *
     * @return  null|false
     */
    public static function analyzeStatic(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        Context $context
    ) {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable ||
            $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            // @todo check this
            return null;
        }

        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($stmt->class->parts[0] === 'parent') {
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
                    $fq_class_name = (string)$context->self;
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }
            } else {
                $aliases = $statements_analyzer->getAliases();

                if ($context->calling_method_id
                    && !$stmt->class instanceof PhpParser\Node\Name\FullyQualified
                ) {
                    $codebase->file_reference_provider->addReferenceToClassMethod(
                        $context->calling_method_id,
                        'use:' . $stmt->class->parts[0] . ':' . \md5($statements_analyzer->getFilePath())
                    );
                }

                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $aliases
                );

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                if ($context->check_classes) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) !== true) {
                        return false;
                    }
                }
            }

            $stmt->class->inferredType = $fq_class_name ? new Type\Union([new TNamedObject($fq_class_name)]) : null;
        }

        if ($stmt->name instanceof PhpParser\Node\VarLikeIdentifier) {
            $prop_name = $stmt->name->name;
        } elseif (isset($stmt->name->inferredType)
            && $stmt->name->inferredType->isSingleStringLiteral()
        ) {
            $prop_name = $stmt->name->inferredType->getSingleStringLiteral()->value;
        } else {
            $prop_name = null;
        }

        if ($fq_class_name &&
            $context->check_classes &&
            $context->check_variables &&
            $prop_name &&
            !ExpressionAnalyzer::isMock($fq_class_name)
        ) {
            $var_id = ExpressionAnalyzer::getVarId(
                $stmt,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            $property_id = $fq_class_name . '::$' . $prop_name;

            if ($codebase->server_mode) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $property_id
                );
            }

            if ($var_id && $context->hasVariable($var_id, $statements_analyzer)) {
                // we don't need to check anything
                $stmt->inferredType = $context->vars_in_scope[$var_id];

                if ($context->collect_references) {
                    // log the appearance
                    $codebase->properties->propertyExists(
                        $property_id,
                        $context->calling_method_id,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    );
                }

                if ($codebase->server_mode
                    && (!$context->collect_initializations
                        && !$context->collect_mutations)
                    && isset($stmt->inferredType)
                ) {
                    $codebase->analyzer->addNodeType(
                        $statements_analyzer->getFilePath(),
                        $stmt->name,
                        (string) $stmt->inferredType
                    );
                }

                return null;
            }

            if (!$codebase->properties->propertyExists(
                $property_id,
                $context->calling_method_id,
                $context->collect_references ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null
            )
            ) {
                if (IssueBuffer::accepts(
                    new UndefinedPropertyFetch(
                        'Static property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                return;
            }

            if (ClassLikeAnalyzer::checkPropertyVisibility(
                $property_id,
                $context->self,
                $statements_analyzer,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues()
            ) === false) {
                return false;
            }

            $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                $fq_class_name . '::$' . $prop_name
            );

            $class_storage = $codebase->classlike_storage_provider->get((string)$declaring_property_class);
            $property = $class_storage->properties[$prop_name];

            if ($var_id) {
                $context->vars_in_scope[$var_id] = $property->type
                    ? clone $property->type
                    : Type::getMixed();

                $stmt->inferredType = clone $context->vars_in_scope[$var_id];

                if ($codebase->server_mode
                    && (!$context->collect_initializations
                        && !$context->collect_mutations)
                ) {
                    $codebase->analyzer->addNodeType(
                        $statements_analyzer->getFilePath(),
                        $stmt->name,
                        (string) $stmt->inferredType
                    );
                }
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        }

        return null;
    }
}
