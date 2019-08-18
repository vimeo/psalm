<?php
namespace Psalm\Internal\Provider;

use const PHP_VERSION;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Plugin\Hook\MethodExistenceProviderInterface;
use Psalm\StatementsSource;
use function strtolower;
use function version_compare;

class MethodExistenceProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     string,
     *     string,
     *     ?StatementsSource=,
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
     * @param  class-string<MethodExistenceProviderInterface> $class
     *
     * @return void
     */
    public function registerClass(string $class)
    {
        $callable = \Closure::fromCallable([$class, 'doesMethodExist']);

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            /** @psalm-suppress MixedTypeCoercion */
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * /**
     * @param \Closure(
     *     string,
     *     string,
     *     ?StatementsSource=,
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
    public function doesMethodExist(
        string $fq_classlike_name,
        string $method_name_lowercase,
        StatementsSource $source = null,
        CodeLocation $code_location = null
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $method_handler) {
            $method_exists = $method_handler(
                $fq_classlike_name,
                $method_name_lowercase,
                $source,
                $code_location
            );

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        return null;
    }
}
