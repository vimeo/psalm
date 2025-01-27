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

/**
 * @internal
 */
final class HighOrderFunctionArgInfo
{
    public const TYPE_FIRST_CLASS_CALLABLE = 'first-class-callable';
    public const TYPE_CLASS_CALLABLE = 'class-callable';
    public const TYPE_STRING_CALLABLE = 'string-callable';
    public const TYPE_CALLABLE = 'callable';

    /**
     * @psalm-param HighOrderFunctionArgInfo::TYPE_* $type
     */
    public function __construct(
        private readonly string $type,
        private readonly FunctionLikeStorage $function_storage,
        private readonly ?ClassLikeStorage $class_storage = null,
    ) {
    }

    public function getTemplates(): TemplateResult
    {
        $templates = $this->class_storage
            ? [...$this->function_storage->template_types ?? [], ...$this->class_storage->template_types ?? []]
            : $this->function_storage->template_types ?? [];

        return new TemplateResult($templates, []);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFunctionType(): Union
    {
        return match ($this->type) {
            self::TYPE_FIRST_CLASS_CALLABLE => new Union([
                new TClosure(
                    'Closure',
                    $this->function_storage->params,
                    $this->function_storage->return_type,
                    $this->function_storage->pure,
                ),
            ]),
            self::TYPE_STRING_CALLABLE, self::TYPE_CLASS_CALLABLE => new Union([
                new TCallable(
                    'callable',
                    $this->function_storage->params,
                    $this->function_storage->return_type,
                    $this->function_storage->pure,
                ),
            ]),
            default => $this->function_storage->return_type ?? Type::getMixed(),
        };
    }
}
