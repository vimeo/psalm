<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\PropertyTypeProviderInterface;

class PropertyTypeProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     string,
     *     string,
     *     bool,
     *     ?StatementsSource=,
     *     ?Context=
     *   ) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<PropertyTypeProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function registerClass(string $class)
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            /**
             * @psalm-suppress UndefinedMethod
             * @var \Closure
             */
            $callable = \Closure::fromCallable([$class, 'getPropertyType']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('getPropertyType')->getClosure(new $class);

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
     *     string,
     *     string,
     *     bool,
     *     ?StatementsSource=,
     *     ?Context=
     *   ) : ?Type\Union $c
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
     * @return ?Type\Union
     */
    public function getPropertyType(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        StatementsSource $source = null,
        Context $context = null
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $property_handler) {
            $property_type = $property_handler(
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $source,
                $context
            );

            if ($property_type !== null) {
                return $property_type;
            }
        }

        return null;
    }
}
