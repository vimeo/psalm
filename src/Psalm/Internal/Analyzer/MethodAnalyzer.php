<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use LogicException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\InvalidEnumMethod;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\MethodSignatureMustOmitReturnType;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\UndefinedMagicMethod;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use UnexpectedValueException;

use function in_array;
use function strtolower;

/**
 * @internal
 * @extends FunctionLikeAnalyzer<PhpParser\Node\Stmt\ClassMethod>
 */
final class MethodAnalyzer extends FunctionLikeAnalyzer
{
    use UnserializeMemoryUsageSuppressionTrait;
    // https://github.com/php/php-src/blob/a83923044c48982c80804ae1b45e761c271966d3/Zend/zend_enum.c#L77-L95
    private const FORBIDDEN_ENUM_METHODS = [
        '__construct',
        '__destruct',
        '__clone',
        '__get',
        '__set',
        '__unset',
        '__isset',
        '__tostring',
        '__debuginfo',
        '__serialize',
        '__unserialize',
        '__sleep',
        '__wakeup',
        '__set_state',
    ];

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        PhpParser\Node\Stmt\ClassMethod $function,
        SourceAnalyzer $source,
        ?MethodStorage $storage = null,
    ) {
        $codebase = $source->getCodebase();

        $method_name_lc = strtolower((string) $function->name);

        $source_fqcln = (string) $source->getFQCLN();

        $source_fqcln_lc = strtolower($source_fqcln);

        $method_id = new MethodIdentifier($source_fqcln, $method_name_lc);

        if (!$storage) {
            try {
                $storage = $codebase->methods->getStorage($method_id);
            } catch (UnexpectedValueException $e) {
                $class_storage = $codebase->classlike_storage_provider->get($source_fqcln_lc);

                if (!$class_storage->parent_classes) {
                    throw $e;
                }

                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

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
     * @param  array<string>   $suppressed_issues
     */
    public static function checkStatic(
        MethodIdentifier $method_id,
        bool $self_call,
        bool $is_context_dynamic,
        Codebase $codebase,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?bool &$is_dynamic_this_method = false,
    ): void {
        $codebase_methods = $codebase->methods;

        if ($method_id->fq_class_name === 'Closure'
            && $method_id->method_name === 'fromcallable'
        ) {
            return;
        }

        $original_method_id = $method_id;
        $with_pseudo = true;

        $method_id = $codebase_methods->getDeclaringMethodId($method_id, $with_pseudo);

        if (!$method_id) {
            if (InternalCallMapHandler::inCallMap((string) $original_method_id)) {
                return;
            }

            throw new LogicException('Declaring method for ' . $original_method_id . ' should not be null');
        }

        $storage = $codebase_methods->getStorage($method_id, $with_pseudo);

        if (!$storage->is_static) {
            if ($self_call) {
                if (!$is_context_dynamic) {
                    if (IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Method ' . $codebase_methods->getCasedMethodId($method_id) .
                                ' is not static, but is called ' .
                                'using self::',
                            $code_location,
                        ),
                        $suppressed_issues,
                    )) {
                        return;
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
                        $code_location,
                    ),
                    $suppressed_issues,
                )) {
                    return;
                }
            }
        }
    }

    /**
     * The `analysis_php_version_id` that introduced a native method, unless a polyfill provides it.
     *
     * A method inherits the availability of its declaring class (a class introduced in PHP 8.1 has
     * no method available before 8.1) unless the method itself carries a later `@since`, which then
     * takes priority.
     */
    public static function getMethodSincePhpVersionId(
        Codebase $codebase,
        MethodIdentifier $method_id,
        bool $with_pseudo = false,
    ): ?int {
        try {
            $method_storage = $codebase->methods->getStorage($method_id, $with_pseudo);
        } catch (UnexpectedValueException) {
            return null;
        }

        $defining_class = $method_storage->defining_fqcln ?? $method_id->fq_class_name;

        $method_since_id = $method_storage->since_php_version_id;
        if ($method_since_id === null && $codebase->classlike_storage_provider->has($defining_class)) {
            $defining_class_storage = $codebase->classlike_storage_provider->get($defining_class);
            $method_since_id = $defining_class_storage->since_php_version_id;

            // A native method with no `@since` on it or its class is dated by the versioned
            // callmaps (e.g. ReflectionClass::isEnum(), added in PHP 8.1 to a pre-existing class).
            if ($method_since_id === null && !$defining_class_storage->user_defined) {
                $method_since_id = InternalCallMapHandler::getIntroducingPhpVersionId(
                    $defining_class . '::' . $method_id->method_name,
                );
            }
        }

        return $method_since_id !== null && !$codebase->isClassLikePolyfilled($defining_class)
            ? $method_since_id
            : null;
    }

    /**
     * Reports a native method used below the PHP version that introduced it.
     *
     * @param  string[]     $suppressed_issues
     */
    public static function checkMethodAvailability(
        Codebase $codebase,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
        bool $with_pseudo = false,
        ?Context $context = null,
    ): void {
        // The method is known to Psalm (its stubbed signature is always loaded so analysis is
        // unaffected), but a native method introduced in a later PHP version is undefined when
        // analysing an older version without a polyfill. The issue is reported without treating
        // the method as unknown, so its stubbed signature is still used for the rest of analysis.
        $method_since_id = self::getMethodSincePhpVersionId($codebase, $method_id, $with_pseudo);

        if ($method_since_id !== null
            && ($codebase->getGuardedPhpVersionId($context) ?? $codebase->analysis_php_version_id) < $method_since_id
        ) {
            IssueBuffer::maybeAdd(
                new UndefinedMethod(
                    'Method ' . $codebase->methods->getCasedMethodId($method_id) . ' '
                        . $codebase->getUnavailableSymbolMessageSuffix($method_since_id),
                    $code_location,
                    (string) $method_id,
                ),
                $suppressed_issues,
            );
        }
    }

    /**
     * @param  string[]     $suppressed_issues
     * @param  lowercase-string|null  $calling_method_id
     */
    public static function checkMethodExists(
        Codebase $codebase,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?string $calling_method_id = null,
        bool $with_pseudo = false,
        ?Context $context = null,
    ): ?bool {
        if ($codebase->methodExists(
            method_id: $method_id,
            calling_method_id: $calling_method_id,
            code_location: !$calling_method_id
                || $calling_method_id !== strtolower((string) $method_id)
                ? $code_location
                : null,
            source_file_path: $code_location->file_path,
            with_pseudo: $with_pseudo,
        )) {
            self::checkMethodAvailability(
                $codebase,
                $method_id,
                $code_location,
                $suppressed_issues,
                $with_pseudo,
                $context,
            );

            return true;
        }

        if ($with_pseudo) {
            if (IssueBuffer::accepts(
                new UndefinedMagicMethod(
                    'Magic method ' . $method_id . ' does not exist',
                    $code_location,
                    (string) $method_id,
                ),
                $suppressed_issues,
            )) {
                return false;
            }
        } else {
            if (IssueBuffer::accepts(
                new UndefinedMethod('Method ' . $method_id . ' does not exist', $code_location, (string) $method_id),
                $suppressed_issues,
            )) {
                return false;
            }
        }

        return null;
    }

    public static function isMethodVisible(
        MethodIdentifier $method_id,
        Context $context,
        StatementsSource $source,
    ): bool {
        $codebase = $source->getCodebase();

        $fq_classlike_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($codebase->methods->visibility_provider->has($fq_classlike_name)) {
            $method_visible = $codebase->methods->visibility_provider->isMethodVisible(
                $source,
                $fq_classlike_name,
                $method_name,
                $context,
                null,
            );

            if ($method_visible !== null) {
                return $method_visible;
            }
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            // this can happen for methods in the callmap that were not reflected
            return true;
        }

        $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

        $appearing_method_class = null;

        if ($appearing_method_id) {
            $appearing_method_class = $appearing_method_id->fq_class_name;

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $context->self) {
                return true;
            }
        }

        $declaring_method_class = $declaring_method_id->fq_class_name;

        if ($source->getSource() instanceof TraitAnalyzer
            && strtolower($declaring_method_class) === strtolower((string) $source->getFQCLN())
        ) {
            return true;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PUBLIC:
                return true;

            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                return $context->self && $appearing_method_class === $context->self;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                if (!$context->self) {
                    return false;
                }

                if ($appearing_method_class
                    && $codebase->classExtends($appearing_method_class, $context->self)
                ) {
                    return true;
                }

                if ($appearing_method_class
                    && !$codebase->classExtends($context->self, $appearing_method_class)
                ) {
                    return false;
                }
        }

        return true;
    }

    /**
     * Check that __clone, __construct, and __destruct do not have a return type
     * hint in their signature.
     */
    public static function checkMethodSignatureMustOmitReturnType(
        MethodStorage $method_storage,
        CodeLocation $code_location,
    ): void {
        if ($method_storage->signature_return_type === null) {
            return;
        }

        if ($method_storage->cased_name === null) {
            return;
        }

        $method_name_lc = strtolower($method_storage->cased_name);
        $methodsOfInterest = ['__clone', '__construct', '__destruct'];

        if (in_array($method_name_lc, $methodsOfInterest, true)) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMustOmitReturnType(
                    'Method ' . $method_storage->cased_name . ' must not declare a return type',
                    $code_location,
                ),
            );
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function getMethodId(?string $context_self = null): MethodIdentifier
    {
        $function_name = (string)$this->function->name;

        return new MethodIdentifier(
            $context_self ?: (string) $this->source->getFQCLN(),
            strtolower($function_name),
        );
    }

    public static function checkForbiddenEnumMethod(MethodStorage $method_storage, ClassLikeStorage $enum_storage): void
    {
        if ($method_storage->cased_name === null || $method_storage->location === null) {
            return;
        }

        $method_name_lc = strtolower($method_storage->cased_name);
        if (in_array($method_name_lc, self::FORBIDDEN_ENUM_METHODS, true)) {
            IssueBuffer::maybeAdd(new InvalidEnumMethod(
                'Enums cannot define ' . $method_storage->cased_name,
                $method_storage->location,
                $method_storage->defining_fqcln . '::' . $method_storage->cased_name,
            ));
        }

        if ($method_name_lc === 'cases') {
            IssueBuffer::maybeAdd(new InvalidEnumMethod(
                'Enums cannot define ' . $method_storage->cased_name,
                $method_storage->location,
                $method_storage->defining_fqcln . '::' . $method_storage->cased_name,
            ));
        }

        if ($enum_storage->enum_type && ($method_name_lc === 'from' || $method_name_lc === 'tryfrom')) {
            IssueBuffer::maybeAdd(new InvalidEnumMethod(
                'Enums cannot define ' . $method_storage->cased_name,
                $method_storage->location,
                $method_storage->defining_fqcln . '::' . $method_storage->cased_name,
            ));
        }
    }
}
