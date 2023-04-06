<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function array_merge;

/**
 * @internal
 */
final class HighOrderFunctionArgInfo
{
    public const TYPE_FIRST_CLASS_CALLABLE = 'first-class-callable';
    public const TYPE_CLASS_CALLABLE = 'class-callable';
    public const TYPE_STRING_CALLABLE = 'string-callable';
    public const TYPE_CALLABLE = 'callable';

    /** @psalm-var HighOrderFunctionArgInfo::TYPE_* */
    private string $type;
    private FunctionLikeStorage $function_storage;
    private ?ClassLikeStorage $class_storage;

    /**
     * @psalm-param HighOrderFunctionArgInfo::TYPE_* $type
     */
    public function __construct(
        string $type,
        FunctionLikeStorage $function_storage,
        ClassLikeStorage $class_storage = null
    ) {
        $this->type = $type;
        $this->function_storage = $function_storage;
        $this->class_storage = $class_storage;
    }

    public function getTemplates(): TemplateResult
    {
        $templates = $this->class_storage
            ? array_merge(
                $this->function_storage->template_types ?? [],
                $this->class_storage->template_types ?? [],
            )
            : $this->function_storage->template_types ?? [];

        return new TemplateResult($templates, []);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFunctionType(): Union
    {
        switch ($this->type) {
            case self::TYPE_FIRST_CLASS_CALLABLE:
                return new Union([
                    new TClosure(
                        'Closure',
                        $this->function_storage->params,
                        $this->function_storage->return_type,
                        $this->function_storage->pure,
                    ),
                ]);

            case self::TYPE_STRING_CALLABLE:
            case self::TYPE_CLASS_CALLABLE:
                return new Union([
                    new TCallable(
                        'callable',
                        $this->function_storage->params,
                        $this->function_storage->return_type,
                        $this->function_storage->pure,
                    ),
                ]);

            default:
                return $this->function_storage->return_type ?? Type::getMixed();
        }
    }
}
