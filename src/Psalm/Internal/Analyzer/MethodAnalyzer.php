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
use Psalm\Interner;
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
use Psalm\StrId;
use UnexpectedValueException;

use function in_array;

/**
 * @internal
 * @extends FunctionLikeAnalyzer<PhpParser\Node\Stmt\ClassMethod>
 */
final class MethodAnalyzer extends FunctionLikeAnalyzer
{
    use UnserializeMemoryUsageSuppressionTrait;

    private readonly MethodIdentifier $method_id;

    // https://github.com/php/php-src/blob/a83923044c48982c80804ae1b45e761c271966d3/Zend/zend_enum.c#L77-L95
    private const FORBIDDEN_ENUM_METHODS = [
        StrId::__construct,
        StrId::__destruct,
        StrId::__clone,
        StrId::__get,
        StrId::__set,
        StrId::__unset,
        StrId::__isset,
        StrId::__toString,
        StrId::__debugInfo,
        StrId::__serialize,
        StrId::__unserialize,
        StrId::__sleep,
        StrId::__wakeup,
        StrId::__set_state,
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

        $method_name = Interner::intern((string) $function->name);

        $source_fqcln = $source->getFQCLN();

        if ($source_fqcln === null) {
            throw new UnexpectedValueException('Methods must be declared inside a class-like');
        }

        $method_id = $this->method_id = new MethodIdentifier($source_fqcln, $method_name);

        if (!$storage) {
            try {
                $storage = $codebase->methods->getStorage($method_id);
            } catch (UnexpectedValueException $e) {
                $class_storage = $codebase->classlike_storage_provider->get($source_fqcln);

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

        if ($method_id->fq_class_name === StrId::Closure
            && $method_id->method_name === StrId::fromCallable
        ) {
            return;
        }

        $original_method_id = $method_id;
        $with_pseudo = true;

        $method_id = $codebase_methods->getDeclaringMethodId($method_id, $with_pseudo);

        if (!$method_id) {
            if (InternalCallMapHandler::inCallMap($original_method_id)) {
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
     * @param  string[]     $suppressed_issues
     */
    public static function checkMethodExists(
        Codebase $codebase,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?MethodIdentifier $calling_method_id = null,
        bool $with_pseudo = false,
    ): ?bool {
        if ($codebase->methodExists(
            method_id: $method_id,
            calling_method_id: $calling_method_id,
            code_location: !$calling_method_id
                || !$calling_method_id->equals($method_id)
                ? $code_location
                : null,
            source_file_path: $code_location->file_path,
            with_pseudo: $with_pseudo,
        )) {
            return true;
        }

        if ($with_pseudo) {
            if (IssueBuffer::accepts(
                new UndefinedMagicMethod(
                    'Magic method ' . $method_id . ' does not exist',
                    $code_location,
                    $method_id,
                ),
                $suppressed_issues,
            )) {
                return false;
            }
        } else {
            if (IssueBuffer::accepts(
                new UndefinedMethod('Method ' . $method_id . ' does not exist', $code_location, $method_id),
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

        $source_fqcln = $source->getFQCLN();
        if ($source->getSource() instanceof TraitAnalyzer
            && $source_fqcln !== null
            && $declaring_method_class === $source_fqcln
        ) {
            return true;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PUBLIC:
                return true;

            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                return $context->self !== null && $appearing_method_class === $context->self;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                if ($context->self === null) {
                    return false;
                }

                if ($appearing_method_class !== null
                    && $codebase->classExtends($appearing_method_class, $context->self)
                ) {
                    return true;
                }

                if ($appearing_method_class !== null
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

        $method_name = $method_storage->cased_name;

        if ($method_name === StrId::__clone
            || $method_name === StrId::__construct
            || $method_name === StrId::__destruct
        ) {
            IssueBuffer::maybeAdd(
                new MethodSignatureMustOmitReturnType(
                    'Method ' . Interner::str($method_storage->cased_name) . ' must not declare a return type',
                    $code_location,
                ),
            );
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function getMethodId(?int $context_self = null): MethodIdentifier
    {
        if ($context_self === null || $context_self === $this->method_id->fq_class_name) {
            return $this->method_id;
        }

        return new MethodIdentifier($context_self, $this->method_id->method_name);
    }

    public static function checkForbiddenEnumMethod(MethodStorage $method_storage, ClassLikeStorage $enum_storage): void
    {
        if ($method_storage->cased_name === null || $method_storage->location === null) {
            return;
        }

        $method_name = $method_storage->cased_name;
        $method_id = new MethodIdentifier(
            $method_storage->defining_fqcln ?? $enum_storage->name,
            $method_name,
        );
        $message = 'Enums cannot define ' . Interner::str($method_storage->cased_name);
        if (in_array($method_name, self::FORBIDDEN_ENUM_METHODS, true)) {
            IssueBuffer::maybeAdd(new InvalidEnumMethod(
                $message,
                $method_storage->location,
                $method_id,
            ));
        }

        if ($method_name === StrId::cases) {
            IssueBuffer::maybeAdd(new InvalidEnumMethod(
                $message,
                $method_storage->location,
                $method_id,
            ));
        }

        if ($enum_storage->enum_type
            && ($method_name === StrId::from || $method_name === StrId::tryFrom)
        ) {
            IssueBuffer::maybeAdd(new InvalidEnumMethod(
                $message,
                $method_storage->location,
                $method_id,
            ));
        }
    }
}
