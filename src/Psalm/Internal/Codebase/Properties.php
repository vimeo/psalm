<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\PropertyIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\PropertyExistenceProvider;
use Psalm\Internal\Provider\PropertyTypeProvider;
use Psalm\Internal\Provider\PropertyVisibilityProvider;
use Psalm\StatementsSource;
use Psalm\Storage\PropertyStorage;
use Psalm\Type\Union;
use UnexpectedValueException;

/**
 * @internal
 *
 * Handles information about class properties
 */
final class Properties
{
    public PropertyExistenceProvider $property_existence_provider;

    public PropertyTypeProvider $property_type_provider;

    public PropertyVisibilityProvider $property_visibility_provider;


    public function __construct(
        private readonly ClassLikeStorageProvider $classlike_storage_provider,
        private readonly ClassLikes $classlikes,
    ) {
        $this->property_existence_provider = new PropertyExistenceProvider();
        $this->property_visibility_provider = new PropertyVisibilityProvider();
        $this->property_type_provider = new PropertyTypeProvider();
    }

    /**
     * Whether or not a given property exists
     */
    public function propertyExists(
        Codebase $codebase,
        PropertyIdentifier $property_id,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null,
    ): bool {
        $fq_class_name = $property_id->fq_class_name;
        $property_name = $property_id->property_name;

        if ($this->property_existence_provider->has($fq_class_name)) {
            $property_exists = $this->property_existence_provider->doesPropertyExist(
                $fq_class_name,
                $property_name,
                $read_mode,
                $source,
                $context,
                $code_location,
            );

            if ($property_exists !== null) {
                return $property_exists;
            }
        }

        $class_storage = $this->classlikes->getStorageFor($fq_class_name);

        if (!$class_storage) {
            return false;
        }

        if ($source
            && $context
            && $context->self !== $fq_class_name
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->addReferenceToClass($fq_class_name, $code_location, $context, $source->getFilePath());
        }

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];

            $codebase->addReferenceToProperty(
                $declaring_property_class,
                $property_name,
                $read_mode,
                $code_location,
                $context,
                $source?->getFilePath(),
            );

            return true;
        }

        $codebase->addReferenceToMissingProperty(
            $fq_class_name,
            $property_name,
            $code_location,
            $context,
            $source?->getFilePath(),
        );
        return false;
    }

    public function getDeclaringClassForProperty(
        PropertyIdentifier $property_id,
        bool $read_mode,
        ?StatementsSource $source = null,
    ): ?int {
        $fq_class_name = $property_id->fq_class_name;
        $property_name = $property_id->property_name;

        if ($this->property_existence_provider->has($fq_class_name)) {
            if ($this->property_existence_provider->doesPropertyExist(
                $fq_class_name,
                $property_name,
                $read_mode,
                $source,
                null,
            )) {
                return $fq_class_name;
            }
        }

        $class_storage = $this->classlikes->getStorageFor($fq_class_name);

        if ($class_storage && isset($class_storage->declaring_property_ids[$property_name])) {
            return $class_storage->declaring_property_ids[$property_name];
        }

        return null;
    }

    /**
     * Get the class this property appears in (vs is declared in, which could give a trait)
     */
    public function getAppearingClassForProperty(
        PropertyIdentifier $property_id,
        bool $read_mode,
        ?StatementsSource $source = null,
    ): ?int {
        $fq_class_name = $property_id->fq_class_name;
        $property_name = $property_id->property_name;

        if ($this->property_existence_provider->has($fq_class_name)) {
            if ($this->property_existence_provider->doesPropertyExist(
                $fq_class_name,
                $property_name,
                $read_mode,
                $source,
                null,
            )) {
                return $fq_class_name;
            }
        }

        $class_storage = $this->classlikes->getStorageFor($fq_class_name);

        if ($class_storage && isset($class_storage->appearing_property_ids[$property_name])) {
            return $class_storage->appearing_property_ids[$property_name];
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStorage(PropertyIdentifier $property_id): PropertyStorage
    {
        $property_name = $property_id->property_name;

        $class_storage = $this->classlike_storage_provider->get($property_id->fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

            if (isset($declaring_class_storage->properties[$property_name])) {
                return $declaring_class_storage->properties[$property_name];
            }
        }

        throw new UnexpectedValueException('Property ' . (string) $property_id . ' should exist');
    }

    /**
     * @psalm-mutation-free
     */
    public function hasStorage(PropertyIdentifier $property_id): bool
    {
        $property_name = $property_id->property_name;

        $class_storage = $this->classlike_storage_provider->get($property_id->fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

            return isset($declaring_class_storage->properties[$property_name]);
        }
        return false;
    }

    public function getPropertyType(
        PropertyIdentifier $property_id,
        bool $property_set,
        ?StatementsSource $source = null,
        ?Context $context = null,
    ): ?Union {
        $fq_class_name = $property_id->fq_class_name;
        $property_name = $property_id->property_name;

        if ($this->property_type_provider->has($fq_class_name)) {
            $property_type = $this->property_type_provider->getPropertyType(
                $fq_class_name,
                $property_name,
                !$property_set,
                $source,
                $context,
            );

            if ($property_type !== null) {
                return $property_type;
            }
        }

        $class_storage = $this->classlikes->getStorageFor($fq_class_name);

        if ($class_storage && isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

            if (isset($declaring_class_storage->properties[$property_name])) {
                $storage = $declaring_class_storage->properties[$property_name];
            } else {
                throw new UnexpectedValueException('Property ' . (string) $property_id . ' should exist');
            }
        } else {
            throw new UnexpectedValueException('Property ' . (string) $property_id . ' should exist');
        }

        if ($storage->type) {
            if ($property_set) {
                if (isset($class_storage->pseudo_property_set_types[$property_name])) {
                    return $class_storage->pseudo_property_set_types[$property_name];
                }
            } else {
                if (isset($class_storage->pseudo_property_get_types[$property_name])) {
                    return $class_storage->pseudo_property_get_types[$property_name];
                }
            }

            return $storage->type;
        }

        if (!isset($class_storage->overridden_property_ids[$property_name])) {
            return null;
        }

        foreach ($class_storage->overridden_property_ids[$property_name] as $overridden_property_class) {
            $overridden_storage = $this->getStorage(
                new PropertyIdentifier($overridden_property_class, $property_name),
            );

            if ($overridden_storage->type) {
                return $overridden_storage->type;
            }
        }

        return null;
    }
}
