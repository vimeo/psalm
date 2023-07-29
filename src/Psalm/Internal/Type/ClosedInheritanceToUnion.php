<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_keys;

/**
 * @internal
 */
final class ClosedInheritanceToUnion
{
    public static function map(Union $input, Codebase $codebase): Union
    {
        $new_types = [];
        $meet_inheritors = false;

        foreach ($input->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNamedObject) {
                $storage = $codebase->classlikes->getStorageFor($atomic_type->value);

                if (null === $storage || null === $storage->inheritors) {
                    $new_types[] = $atomic_type;
                    continue;
                }

                $template_result = self::getTemplateResult($atomic_type, $codebase);

                $replaced_inheritors = TemplateInferredTypeReplacer::replace(
                    $storage->inheritors,
                    $template_result,
                    $codebase,
                );

                foreach ($replaced_inheritors->getAtomicTypes() as $replaced_atomic_type) {
                    $new_types[] = $replaced_atomic_type;
                }

                $meet_inheritors = true;
            } else {
                $new_types[] = $atomic_type;
            }
        }

        if (!$meet_inheritors) {
            return $input;
        }

        return $new_types ? $input->setTypes($new_types) : $input;
    }

    private static function getTemplateResult(TNamedObject $object, Codebase $codebase): TemplateResult
    {
        if (!$object instanceof TGenericObject) {
            return new TemplateResult([], []);
        }

        $storage = $codebase->classlikes->getStorageFor($object->value);

        if (null === $storage || null === $storage->template_types) {
            return new TemplateResult([], []);
        }

        $lower_bounds = [];
        $offset = 0;

        foreach ($storage->template_types as $template_name => $templates) {
            foreach (array_keys($templates) as $defining_class) {
                $lower_bounds[$template_name][$defining_class] = $object->type_params[$offset++];
            }
        }

        return new TemplateResult($storage->template_types, $lower_bounds);
    }
}
