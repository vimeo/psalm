<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface as LegacyMethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
use function strtolower;
use function is_subclass_of;

class MethodReturnTypeProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<\Closure(MethodReturnTypeProviderEvent) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     lowercase-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation,
     *     ?array<Type\Union>=,
     *     ?string=,
     *     ?lowercase-string=
     *   ) : ?Type\Union>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];

        $this->registerClass(ReturnTypeProvider\DomNodeAppendChild::class);
        $this->registerClass(ReturnTypeProvider\SimpleXmlElementAsXml::class);
        $this->registerClass(ReturnTypeProvider\PdoStatementReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ClosureFromCallableReturnTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyMethodReturnTypeProviderInterface::class, true)) {
            $callable = \Closure::fromCallable([$class, 'getMethodReturnType']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerLegacyClosure($fq_classlike_name, $callable);
            }
        } elseif (is_subclass_of($class, MethodReturnTypeProviderInterface::class, true)) {
            $callable = \Closure::fromCallable([$class, 'getMethodReturnType']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param \Closure(MethodReturnTypeProviderEvent) : ?Type\Union $c
     */
    public function registerClosure(string $fq_classlike_name, \Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @param  \Closure(
     *     StatementsSource,
     *     string,
     *     lowercase-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation,
     *     ?array<Type\Union>=,
     *     ?string=,
     *     ?lowercase-string=
     *   ) : ?Type\Union $c
     *
     */
    public function registerLegacyClosure(string $fq_classlike_name, \Closure $c): void
    {
        self::$legacy_handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name) : bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]) ||
            isset(self::$legacy_handlers[strtolower($fq_classlike_name)]);
    }

    /**
     * @param list<PhpParser\Node\Arg>  $call_args
     * @param  ?array<Type\Union> $template_type_parameters
     *
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name = null
    ): ?Type\Union {
        foreach (self::$legacy_handlers[strtolower($fq_classlike_name)] ?? [] as $class_handler) {
            $result = $class_handler(
                $statements_source,
                $fq_classlike_name,
                strtolower($method_name),
                $call_args,
                $context,
                $code_location,
                $template_type_parameters,
                $called_fq_classlike_name,
                $called_method_name ? strtolower($called_method_name) : null
            );

            if ($result) {
                return $result;
            }
        }

        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $class_handler) {
            $event = new MethodReturnTypeProviderEvent(
                $statements_source,
                $fq_classlike_name,
                strtolower($method_name),
                $call_args,
                $context,
                $code_location,
                $template_type_parameters,
                $called_fq_classlike_name,
                $called_method_name ? strtolower($called_method_name) : null
            );
            $result = $class_handler($event);

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
