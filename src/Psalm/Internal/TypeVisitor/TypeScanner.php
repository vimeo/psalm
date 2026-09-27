<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Codebase\Scanner;
use Psalm\Storage\FileStorage;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
final class TypeScanner extends TypeVisitor
{
    /**
     * @param array<int, mixed> $phantom_classes class name id => mixed
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Scanner $scanner,
        private readonly ?FileStorage $file_storage,
        private array $phantom_classes,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject) {
            if (!isset($this->phantom_classes[$type->value])) {
                $this->scanner->queueClassLikeForScanning(
                    $type->value,
                    false,
                    !$type->from_docblock,
                    $this->phantom_classes,
                );

                if ($this->file_storage) {
                    $this->file_storage->referenced_classlikes[$type->value] = $type->value;
                }
            }
        }

        if ($type instanceof TClassConstant) {
            $this->scanner->queueClassLikeForScanning(
                $type->fq_classlike_name,
                false,
                !$type->from_docblock,
                $this->phantom_classes,
            );

            if ($this->file_storage) {
                $this->file_storage->referenced_classlikes[$type->fq_classlike_name] = $type->fq_classlike_name;
            }
        }

        if ($type instanceof TLiteralClassString) {
            $this->scanner->queueClassLikeForScanning(
                $type->class_name,
                false,
                !$type->from_docblock,
                $this->phantom_classes,
            );

            if ($this->file_storage) {
                $this->file_storage->referenced_classlikes[$type->class_name] = $type->class_name;
            }
        }

        return null;
    }
}
