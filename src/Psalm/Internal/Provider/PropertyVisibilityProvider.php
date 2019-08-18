<?php
namespace Psalm\Internal\Provider;

use const PHP_VERSION;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\PropertyVisibilityProviderInterface;
use Psalm\StatementsSource;
use function strtolower;
use function version_compare;

class PropertyVisibilityProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     bool,
     *     ?Context=,
     *     ?CodeLocation=
     *   ) : ?bool>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<PropertyVisibilityProviderInterface> $class
     *
     * @return void
     */
    public function registerClass(string $class)
    {
        $callable = \Closure::fromCallable([$class, 'isPropertyVisible']);

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
     *     bool,
     *     ?Context=,
     *     ?CodeLocation=
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
    public function isPropertyVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $property_handler) {
            $property_visible = $property_handler(
                $source,
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $context,
                $code_location
            );

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        return null;
    }
}
