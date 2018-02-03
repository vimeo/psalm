<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InvalidStaticInvocation;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;

class MethodChecker extends FunctionLikeChecker
{
    /**
     * @param PhpParser\Node\FunctionLike $function
     * @param StatementsSource            $source
     * @psalm-suppress MixedAssignment
     */
    public function __construct($function, StatementsSource $source)
    {
        if (!$function instanceof PhpParser\Node\Stmt\ClassMethod) {
            throw new \InvalidArgumentException('Must be called with a ClassMethod');
        }

        parent::__construct($function, $source);
    }

    /**
     * Determines whether a given method is static or not
     *
     * @param  string          $method_id
     * @param  bool            $self_call
     * @param  CodeLocation    $code_location
     * @param  array<string>   $suppressed_issues
     *
     * @return bool
     */
    public static function checkStatic(
        $method_id,
        $self_call,
        ProjectChecker $project_checker,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $codebase_methods = $project_checker->codebase->methods;

        /** @var string */
        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        $storage = $codebase_methods->getStorage($method_id);

        if (!$storage->is_static) {
            if ($self_call) {
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
     *
     * @return bool|null
     */
    public static function checkMethodExists(
        ProjectChecker $project_checker,
        $method_id,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if ($project_checker->codebase->methodExists($method_id, $code_location)) {
            return true;
        }

        if (IssueBuffer::accepts(
            new UndefinedMethod('Method ' . $method_id . ' does not exist', $code_location),
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
    public static function checkMethodNotDeprecated(
        ProjectChecker $project_checker,
        $method_id,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $codebase_methods = $project_checker->codebase->methods;

        $method_id = (string) $codebase_methods->getDeclaringMethodId($method_id);
        $storage = $codebase_methods->getStorage($method_id);

        if ($storage->deprecated) {
            if (IssueBuffer::accepts(
                new DeprecatedMethod(
                    'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                        ' has been marked as deprecated',
                    $code_location
                ),
                $suppressed_issues
            )) {
                return false;
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
        $project_checker = $source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;
        $codebase_methods = $codebase->methods;
        $codebase_classlikes = $codebase->classlikes;

        $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            $method_name = explode('::', $method_id)[1];

            if ($method_name === '__construct') {
                return null;
            }

            throw new \UnexpectedValueException('$declaring_method_id not expected to be null here');
        }

        $appearing_method_id = $codebase_methods->getAppearingMethodId($method_id);

        $appearing_method_class = null;

        if ($appearing_method_id) {
            list($appearing_method_class) = explode('::', $appearing_method_id);

            // if the calling class is the same, we know the method exists, so it must be visible
            if ($appearing_method_class === $calling_context) {
                return null;
            }
        }

        list($declaring_method_class) = explode('::', $declaring_method_id);

        if ($source->getSource() instanceof TraitChecker && $declaring_method_class === $source->getFQCLN()) {
            return null;
        }

        $storage = $project_checker->codebase->methods->getStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeChecker::VISIBILITY_PUBLIC:
                return null;

            case ClassLikeChecker::VISIBILITY_PRIVATE:
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

            case ClassLikeChecker::VISIBILITY_PROTECTED:
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
        $project_checker = $source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            $method_name = explode('::', $method_id)[1];

            if ($method_name === '__construct') {
                return true;
            }

            throw new \UnexpectedValueException('$declaring_method_id not expected to be null here');
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

        if ($source->getSource() instanceof TraitChecker && $declaring_method_class === $source->getFQCLN()) {
            return true;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);

        switch ($storage->visibility) {
            case ClassLikeChecker::VISIBILITY_PUBLIC:
                return true;

            case ClassLikeChecker::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_method_class !== $calling_context) {
                    return false;
                }

                return true;

            case ClassLikeChecker::VISIBILITY_PROTECTED:
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
}
