<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\ImplementedReturnTypeMismatch;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InternalMethod;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MethodSignatureMustOmitReturnType;
use Psalm\Issue\MoreSpecificImplementedParamType;
use Psalm\Issue\LessSpecificImplementedReturnType;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\OverriddenMethodAccess;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;

/**
 * @internal
 */
class MethodAnalyzer extends FunctionLikeAnalyzer
{
    /**
     * @psalm-suppress MixedAssignment
     */
    public function __construct(
        PhpParser\Node\Stmt\ClassMethod $function,
        SourceAnalyzer $source,
        MethodStorage $storage = null
    ) {
        $codebase = $source->getCodebase();

        $real_method_id = $source->getFQCLN() . '::' . strtolower((string) $function->name);

        if (!$storage) {
            try {
                $storage = $codebase->methods->getStorage($real_method_id);
            } catch (\UnexpectedValueException $e) {
                $class_storage = $codebase->classlike_storage_provider->get((string) $source->getFQCLN());

                if (!$class_storage->parent_classes) {
                    throw $e;
                }

                $declaring_method_id = $codebase->methods->getDeclaringMethodId($real_method_id);

                if (!$declaring_method_id) {
                    throw $e;
                }

                // happens for fake constructors
                $storage = $codebase->methods->getStorage($declaring_method_id);
            }
        }

        parent::__construct($function, $source, $storage);
    }

    /**
     * Determines whether a given method is static or not
     *
     * @param  string          $method_id
     * @param  bool            $self_call
     * @param  bool            $is_context_dynamic
     * @param  CodeLocation    $code_location
     * @param  array<string>   $suppressed_issues
     * @param  bool            $is_dynamic_this_method
     *
     * @return bool
     */
    public static function checkStatic(
        $method_id,
        $self_call,
        $is_context_dynamic,
        Codebase $codebase,
        CodeLocation $code_location,
        array $suppressed_issues,
        &$is_dynamic_this_method = false
    ) {
        $codebase_methods = $codebase->methods;

        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if (!$method_id) {
            throw new \LogicException('Method id should not be null');
        }

        $storage = $codebase_methods->getStorage($method_id);

        if (!$storage->is_static) {
            if ($self_call) {
                if (!$is_context_dynamic) {
                    if (IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' is not static, but is called ' .
                                'using self::',
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    $is_dynamic_this_method = true;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidStaticInvocation(
                        'Method ' . $codebase_methods->getCasedMethodId($method_id) .
                            ' is not static, but is called ' .
                            'statically',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  string       $method_id
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     * @param  string|null  $calling_method_id
     *
     * @return bool|null
     */
    public static function checkMethodExists(
        Codebase $codebase,
        $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
        $calling_method_id = null
    ) {
        if ($codebase->methods->methodExists(
            $method_id,
            $calling_method_id,
            $calling_method_id !== $method_id ? $code_location : null
        )) {
            return true;
        }

        if (IssueBuffer::accepts(
            new UndefinedMethod('Method ' . $method_id . ' does not exist', $code_location, $method_id),
            $suppressed_issues
        )) {
            return false;
        }

        return null;
    }

    /**
     * @param  string       $method_id
     * @param  CodeLocation $code_location
     * @param  array        $suppressed_issues
     *
     * @return false|null
     */
    public static function checkMethodNotDeprecatedOrInternal(
        Codebase $codebase,
        Context $context,
        $method_id,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $codebase_methods = $codebase->methods;

        $method_id = (string) $codebase_methods->getDeclaringMethodId($method_id);
        $storage = $codebase_methods->getStorage($method_id);

        if ($storage->deprecated) {
            if (IssueBuffer::accepts(
                new DeprecatedMethod(
                    'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                        ' has been marked as deprecated',
                    $code_location,
                    $method_id
                ),
                $suppressed_issues
            )) {
                // continue
            }
        }

        if ($storage->internal
            && $context->self
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $declaring_class = explode('::', $method_id)[0];
            $self_root = preg_replace('/^([^\\\]+).*/', '$1', $context->self);
            $declaring_root = preg_replace('/^([^\\\]+).*/', '$1', $declaring_class);

            if (strtolower($self_root) !== strtolower($declaring_root)) {
                if (IssueBuffer::accepts(
                    new InternalMethod(
                        'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                            ' has been marked as internal',
                        $code_location,
                        $method_id
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }
        }

        return null;
    }

    /**
     * @param  string           $method_id
     * @param  string|null      $calling_context
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array            $suppressed_issues
     *
     * @return false|null
     */
    public static function checkMethodVisibility(
        $method_id,
        $calling_context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $codebase = $source->getCodebase();
        $codebase_methods = $codebase->methods;
        $codebase_classlikes = $codebase->classlikes;

        $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            $method_name = explode('::', $method_id)[1];

            if ($method_name === '__construct' || $method_id === 'Closure::__invoke') {
                return null;
            }

            throw new \UnexpectedValueException('$declaring_method_id not expected to be null here');
        }

        $appearing_method_id = $codebase_methods->getAppearingMethodId($method_id);

        $appearing_method_class = null;
        $appearing_class_storage = null;
        $appearing_method_name = null;

        if ($appearing_method_id) {
            list($appearing_method_class, $appearing_method_name) = explode('::', $appearing_method_id);

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $calling_context) {
                return null;
            }

            $appearing_class_storage = $codebase->classlike_storage_provider->get($appearing_method_class);
        }

        list($declaring_method_class) = explode('::', $declaring_method_id);

        if ($source->getSource() instanceof TraitAnalyzer && $declaring_method_class === $source->getFQCLN()) {
            return null;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);
        $visibility = $storage->visibility;

        if ($appearing_method_name
            && isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])
        ) {
            $visibility = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
        }

        switch ($visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PUBLIC:
                return null;

            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_method_class !== $calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access private method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }

                return null;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                if (!$calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' . $method_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                if ($appearing_method_class
                    && $codebase_classlikes->classExtends($appearing_method_class, $calling_context)
                ) {
                    return null;
                }

                if ($appearing_method_class
                    && !$codebase_classlikes->classExtends($calling_context, $appearing_method_class)
                ) {
                    if (IssueBuffer::accepts(
                        new InaccessibleMethod(
                            'Cannot access protected method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
        }

        return null;
    }

    /**
     * @param  string           $method_id
     * @param  string|null      $calling_context
     * @param  StatementsSource $source
     *
     * @return bool
     */
    public static function isMethodVisible(
        $method_id,
        $calling_context,
        StatementsSource $source
    ) {
        $codebase = $source->getCodebase();

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            // this can happen for methods in the callmap that were not reflected
            return true;
        }

        $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

        $appearing_method_class = null;

        if ($appearing_method_id) {
            list($appearing_method_class) = explode('::', $appearing_method_id);

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $calling_context) {
                return true;
            }
        }

        list($declaring_method_class) = explode('::', $declaring_method_id);

        if ($source->getSource() instanceof TraitAnalyzer && $declaring_method_class === $source->getFQCLN()) {
            return true;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PUBLIC:
                return true;

            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                return $calling_context && $appearing_method_class === $calling_context;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                if (!$calling_context) {
                    return false;
                }

                if ($appearing_method_class
                    && $codebase->classExtends($appearing_method_class, $calling_context)
                ) {
                    return true;
                }

                if ($appearing_method_class
                    && !$codebase->classExtends($calling_context, $appearing_method_class)
                ) {
                    return false;
                }
        }

        return true;
    }

    /**
     * @param  ClassLikeStorage $implementer_classlike_storage
     * @param  ClassLikeStorage $guide_classlike_storage
     * @param  MethodStorage    $implementer_method_storage
     * @param  MethodStorage    $guide_method_storage
     * @param  CodeLocation     $code_location
     * @param  array            $suppressed_issues
     * @param  bool             $prevent_abstract_override
     * @param  bool             $prevent_method_signature_mismatch
     *
     * @return false|null
     */
    public static function compareMethods(
        Codebase $codebase,
        ClassLikeStorage $implementer_classlike_storage,
        ClassLikeStorage $guide_classlike_storage,
        MethodStorage $implementer_method_storage,
        MethodStorage $guide_method_storage,
        string $implementer_called_class_name,
        int $implementer_visibility,
        CodeLocation $code_location,
        array $suppressed_issues,
        $prevent_abstract_override = true,
        $prevent_method_signature_mismatch = true
    ) {
        $implementer_method_id = $implementer_classlike_storage->name . '::'
            . strtolower($guide_method_storage->cased_name);

        $implementer_declaring_method_id = $codebase->methods->getDeclaringMethodId($implementer_method_id);

        $cased_implementer_method_id = $implementer_classlike_storage->name . '::'
            . $implementer_method_storage->cased_name;

        $cased_guide_method_id = $guide_classlike_storage->name . '::' . $guide_method_storage->cased_name;

        if ($implementer_visibility > $guide_method_storage->visibility) {
            if (IssueBuffer::accepts(
                new OverriddenMethodAccess(
                    'Method ' . $cased_implementer_method_id . ' has different access level than '
                        . $cased_guide_method_id,
                    $code_location
                )
            )) {
                return false;
            }

            return null;
        }

        if ($prevent_abstract_override
            && !$guide_method_storage->abstract
            && $implementer_method_storage->abstract
            && !$guide_classlike_storage->abstract
            && !$guide_classlike_storage->is_interface
        ) {
            if (IssueBuffer::accepts(
                new MethodSignatureMismatch(
                    'Method ' . $cased_implementer_method_id . ' cannot be abstract when inherited method '
                        . $cased_guide_method_id . ' is non-abstract',
                    $code_location
                )
            )) {
                return false;
            }

            return null;
        }

        if ($guide_method_storage->signature_return_type && $prevent_method_signature_mismatch) {
            $guide_signature_return_type = ExpressionAnalyzer::fleshOutType(
                $codebase,
                $guide_method_storage->signature_return_type,
                $guide_classlike_storage->name,
                $guide_classlike_storage->name
            );

            $implementer_signature_return_type = $implementer_method_storage->signature_return_type
                ? ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $implementer_method_storage->signature_return_type,
                    $implementer_classlike_storage->name,
                    $implementer_classlike_storage->name
                ) : null;

            if (!TypeAnalyzer::isContainedByInPhp($implementer_signature_return_type, $guide_signature_return_type)) {
                if ($guide_classlike_storage !== $implementer_classlike_storage) {
                    if (IssueBuffer::accepts(
                        new MethodSignatureMismatch(
                            'Method ' . $cased_implementer_method_id . ' with return type \''
                                . $implementer_signature_return_type . '\' is different to return type \''
                                . $guide_signature_return_type . '\' of inherited method ' . $cased_guide_method_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new ImplementedReturnTypeMismatch(
                            'Method ' . $cased_implementer_method_id . ' with return type \''
                                . $implementer_signature_return_type . '\' is different to return type \''
                                . $guide_signature_return_type . '\' of inherited method ' . $cased_guide_method_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }

                return null;
            }
        } elseif ($guide_method_storage->return_type
            && $implementer_method_storage->return_type
            && $implementer_classlike_storage->user_defined
            && !$guide_classlike_storage->stubbed
        ) {
            $implementer_method_storage_return_type = ExpressionAnalyzer::fleshOutType(
                $codebase,
                $implementer_method_storage->return_type,
                $implementer_classlike_storage->name,
                $implementer_called_class_name
            );

            $guide_method_storage_return_type = ExpressionAnalyzer::fleshOutType(
                $codebase,
                $guide_method_storage->return_type,
                $guide_classlike_storage->name,
                $guide_classlike_storage->name
            );

            $guide_class_name_lc = strtolower($guide_classlike_storage->name);

            if (isset($implementer_classlike_storage->template_type_extends[$guide_class_name_lc])) {
                $map = $implementer_classlike_storage->template_type_extends[$guide_class_name_lc];

                $template_types = [];

                foreach ($map as $key => $atomic_type) {
                    if (is_string($key)) {
                        $template_types[$key] = [new Type\Union([$atomic_type]), $guide_classlike_storage->name];
                    }
                }

                $guide_method_storage_return_type->replaceTemplateTypesWithArgTypes(
                    $template_types,
                    $codebase
                );
            }

            // treat void as null when comparing against docblock implementer
            if ($implementer_method_storage_return_type->isVoid()) {
                $implementer_method_storage_return_type = Type::getNull();
            }

            if ($guide_method_storage_return_type->isVoid()) {
                $guide_method_storage_return_type = Type::getNull();
            }

            if (!TypeAnalyzer::isContainedBy(
                $codebase,
                $implementer_method_storage_return_type,
                $guide_method_storage_return_type,
                false,
                false,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed
            )) {
                // is the declared return type more specific than the inferred one?
                if ($type_coerced) {
                    if (IssueBuffer::accepts(
                        new LessSpecificImplementedReturnType(
                            'The return type \'' . $guide_method_storage_return_type->getId()
                            . '\' for ' . $cased_guide_method_id . ' is more specific than the implemented '
                            . 'return type for ' . $implementer_declaring_method_id . ' \''
                            . $implementer_method_storage_return_type->getId() . '\'',
                            $implementer_method_storage->return_type_location ?: $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new ImplementedReturnTypeMismatch(
                            'The return type \'' . $guide_method_storage_return_type->getId()
                            . '\' for ' . $cased_guide_method_id . ' is different to the implemented '
                            . 'return type for ' . $implementer_declaring_method_id . ' \''
                            . $implementer_method_storage_return_type->getId() . '\'',
                            $implementer_method_storage->return_type_location ?: $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
            }
        }

        foreach ($guide_method_storage->params as $i => $guide_param) {
            if (!isset($implementer_method_storage->params[$i])) {
                if (!$prevent_abstract_override && $i >= $guide_method_storage->required_param_count) {
                    continue;
                }

                if (IssueBuffer::accepts(
                    new MethodSignatureMismatch(
                        'Method ' . $cased_implementer_method_id . ' has fewer parameters than parent method ' .
                            $cased_guide_method_id,
                        $code_location
                    )
                )) {
                    return false;
                }

                return null;
            }

            $implementer_param = $implementer_method_storage->params[$i];

            if ($prevent_method_signature_mismatch
                && $guide_classlike_storage->user_defined
                && $implementer_param->signature_type
                && !TypeAnalyzer::isContainedByInPhp($guide_param->signature_type, $implementer_param->signature_type)
            ) {
                if (IssueBuffer::accepts(
                    new MethodSignatureMismatch(
                        'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' has wrong type \'' .
                            $implementer_param->signature_type . '\', expecting \'' .
                            $guide_param->signature_type . '\' as defined by ' .
                            $cased_guide_method_id,
                        $implementer_method_storage->params[$i]->location
                            ?: $code_location
                    )
                )) {
                    return false;
                }

                return null;
            }

            if ($guide_classlike_storage->user_defined
                && $implementer_param->type
                && $guide_param->type
                && $implementer_param->type->getId() !== $guide_param->type->getId()
            ) {
                $implementer_method_storage_param_type = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $implementer_param->type,
                    $implementer_classlike_storage->name,
                    $implementer_called_class_name
                );

                $guide_method_storage_param_type = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $guide_param->type,
                    $guide_classlike_storage->name,
                    $guide_classlike_storage->name
                );

                $guide_class_name_lc = strtolower($guide_classlike_storage->name);

                if (isset($implementer_classlike_storage->template_type_extends[$guide_class_name_lc])) {
                    $map = $implementer_classlike_storage->template_type_extends[$guide_class_name_lc];

                    $template_types = [];

                    foreach ($map as $key => $atomic_type) {
                        if (is_string($key)) {
                            $template_types[$key] = [new Type\Union([$atomic_type]), $guide_classlike_storage->name];
                        }
                    }

                    $guide_method_storage_param_type->replaceTemplateTypesWithArgTypes(
                        $template_types,
                        $codebase
                    );
                }

                if (!TypeAnalyzer::isContainedBy(
                    $codebase,
                    $guide_method_storage_param_type,
                    $implementer_method_storage_param_type,
                    false,
                    false
                )) {
                    if (IssueBuffer::accepts(
                        new MoreSpecificImplementedParamType(
                            'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' has wrong type \'' .
                                $implementer_method_storage_param_type->getId() . '\', expecting \'' .
                                $guide_method_storage_param_type->getId() . '\' as defined by ' .
                                $cased_guide_method_id,
                            $implementer_method_storage->params[$i]->location
                                ?: $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
            }

            if ($guide_classlike_storage->user_defined && $implementer_param->by_ref !== $guide_param->by_ref) {
                if (IssueBuffer::accepts(
                    new MethodSignatureMismatch(
                        'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' is' .
                            ($implementer_param->by_ref ? '' : ' not') . ' passed by reference, but argument ' .
                            ($i + 1) . ' of ' . $cased_guide_method_id . ' is' . ($guide_param->by_ref ? '' : ' not'),
                        $implementer_method_storage->params[$i]->location
                            ?: $code_location
                    )
                )) {
                    return false;
                }

                return null;
            }

            $implemeneter_param_type = $implementer_method_storage->params[$i]->type;

            $or_null_guide_type = $guide_param->signature_type
                ? clone $guide_param->signature_type
                : null;

            if ($or_null_guide_type) {
                $or_null_guide_type->addType(new Type\Atomic\TNull);
            }

            if (!$guide_classlike_storage->user_defined
                && $guide_param->type
                && !$guide_param->type->hasMixed()
                && !$guide_param->type->from_docblock
                && (
                    !$implemeneter_param_type
                    || (
                        strtolower($implemeneter_param_type->getId()) !== strtolower($guide_param->type->getId())
                        && (
                            !$or_null_guide_type
                            || strtolower($implemeneter_param_type->getId())
                                !== strtolower($or_null_guide_type->getId())
                        )
                    )
                )
            ) {
                if (IssueBuffer::accepts(
                    new MethodSignatureMismatch(
                        'Argument ' . ($i + 1) . ' of ' . $cased_implementer_method_id . ' has wrong type \'' .
                            $implementer_method_storage->params[$i]->type . '\', expecting \'' .
                            $guide_param->type . '\' as defined by ' .
                            $cased_guide_method_id,
                        $implementer_method_storage->params[$i]->location
                            ?: $code_location
                    )
                )) {
                    return false;
                }

                return null;
            }
        }

        if ($guide_classlike_storage->user_defined
            && ($guide_classlike_storage->is_interface || $implementer_method_storage->cased_name !== '__construct')
            && $implementer_method_storage->required_param_count > $guide_method_storage->required_param_count
        ) {
            if (IssueBuffer::accepts(
                new MethodSignatureMismatch(
                    'Method ' . $cased_implementer_method_id . ' has more required parameters than parent method ' .
                        $cased_guide_method_id,
                    $code_location
                )
            )) {
                return false;
            }

            return null;
        }
    }

    /**
     * Check that __clone, __construct, and __destruct do not have a return type
     * hint in their signature.
     *
     * @param  MethodStorage $method_storage
     * @param  CodeLocation  $code_location
     * @return false|null
     */
    public static function checkMethodSignatureMustOmitReturnType(
        MethodStorage $method_storage,
        CodeLocation $code_location
    ) {
        if ($method_storage->signature_return_type === null) {
            return null;
        }

        $cased_method_name = $method_storage->cased_name;
        $methodsOfInterest = ['__clone', '__construct', '__destruct'];
        if (in_array($cased_method_name, $methodsOfInterest)) {
            if (IssueBuffer::accepts(
                new MethodSignatureMustOmitReturnType(
                    'Method ' . $cased_method_name . ' must not declare a return type',
                    $code_location
                )
            )) {
                return false;
            }
        }

        return null;
    }
}
