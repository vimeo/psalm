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
    private bool $first_class_callable;
    private FunctionLikeStorage $function_storage;
    private ?ClassLikeStorage $class_storage;

    public function __construct(
        bool $first_class_callable,
        FunctionLikeStorage $function_storage,
        ClassLikeStorage $class_storage = null
    ) {
        $this->first_class_callable = $first_class_callable;
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

    public function isInvokableClassCallable(): bool
    {
        return null !== $this->class_storage;
    }

    public function isFirstClassCallable(): bool
    {
        return $this->first_class_callable;
    }

    public function getFunctionType(): Union
    {
        if ($this->isFirstClassCallable()) {
            return $this->asFirstClassCallable();
        }

        if ($this->isInvokableClassCallable()) {
            return $this->asInvokableClassCallable();
        }

        return $this->asHighOrderFunction();
    }

    public function asFirstClassCallable(): Union
    {
        return new Union([
            new TClosure(
                'Closure',
                $this->function_storage->params,
                $this->function_storage->return_type,
                $this->function_storage->pure,
            ),
        ]);
    }

    public function asInvokableClassCallable(): Union
    {
        return new Union([
            new TCallable(
                'callable',
                $this->function_storage->params,
                $this->function_storage->return_type,
                $this->function_storage->pure,
            ),
        ]);
    }

    public function asHighOrderFunction(): Union
    {
        return $this->function_storage->return_type ?? Type::getMixed();
    }
}
