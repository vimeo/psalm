<?php
namespace Psalm\Internal\Provider;

use const PHP_VERSION;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\MethodVisibilityProviderInterface;
use Psalm\StatementsSource;
use function strtolower;
use function version_compare;

class MethodVisibilityProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     Context,
     *     ?CodeLocation
     *   ) : ?bool>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<MethodVisibilityProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     *
     * @return void
     */
    public function registerClass(string $class)
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            /**
             * @psalm-suppress UndefinedMethod
             *
             * @var \Closure
             */
            $callable = \Closure::fromCallable([$class, 'isMethodVisible']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('isMethodVisible')->getClosure(new $class);

            if (!$callable) {
                throw new \UnexpectedValueException('Callable must not be null');
            }
        }

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            /** @psalm-suppress MixedTypeCoercion */
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * /**
     * @param \Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     Context,
     *     ?CodeLocation
     *   ) : ?bool $c
     *
     * @return void
     */
    public function registerClosure(string $fq_classlike_name, \Closure $c)
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name) : bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    /**
     * @param  array<PhpParser\Node\Arg>  $call_args
     *
     * @return ?bool
     */
    public function isMethodVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name,
        Context $context,
        CodeLocation $code_location = null
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $method_handler) {
            $method_visible = $method_handler(
                $source,
                $fq_classlike_name,
                $method_name,
                $context,
                $code_location
            );

            if ($method_visible !== null) {
                return $method_visible;
            }
        }

        return null;
    }
}
