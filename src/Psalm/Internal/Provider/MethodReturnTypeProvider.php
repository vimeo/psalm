<?php
namespace Psalm\Internal\Provider;

use const PHP_VERSION;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
use function strtolower;
use function version_compare;

class MethodReturnTypeProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation,
     *     ?array<Type\Union>=,
     *     ?string=,
     *     ?string=
     *   ) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->registerClass(ReturnTypeProvider\DomNodeAppendChild::class);
        $this->registerClass(ReturnTypeProvider\SimpleXmlElementAsXml::class);
    }

    /**
     * @param  class-string<MethodReturnTypeProviderInterface> $class
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
            $callable = \Closure::fromCallable([$class, 'getMethodReturnType']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('getMethodReturnType')->getClosure(new $class);

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
     * @param  \Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation,
     *     ?array<Type\Union>=,
     *     ?string=,
     *     ?string=
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
     * @param array<PhpParser\Node\Arg>  $call_args
     * @param  ?array<Type\Union> $template_type_parameters
     *
     * @return  ?Type\Union
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        array $template_type_parameters = null,
        string $called_fq_classlike_name = null,
        string $called_method_name = null
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $class_handler) {
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

        return null;
    }
}
