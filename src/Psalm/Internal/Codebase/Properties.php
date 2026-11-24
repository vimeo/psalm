<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\PropertyExistenceProvider;
use Psalm\Internal\Provider\PropertyTypeProvider;
use Psalm\Internal\Provider\PropertyVisibilityProvider;
use Psalm\StatementsSource;
use Psalm\Storage\PropertyStorage;
use Psalm\Type\Union;
use UnexpectedValueException;

use function explode;
use function ltrim;
use function str_starts_with;
use function strtolower;

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
        string $property_id,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null,
    ): bool {
        // remove leading backslash if it exists
        $property_id = ltrim($property_id, '\\');

        [$fq_class_name, $property_name] = explode('::$', $property_id);
        $fq_class_name_lc = strtolower($fq_class_name);

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
        ) {
            $codebase->addReferenceToClass($fq_class_name_lc, $code_location, $context, $source->getFilePath());
        }

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = strtolower($class_storage->declaring_property_ids[$property_name]);

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
            $fq_class_name_lc,
            $property_name,
            $code_location,
            $context,
            $source?->getFilePath(),
        );
        return false;
    }

    public function getDeclaringClassForProperty(
        string $property_id,
        bool $read_mode,
        ?StatementsSource $source = null,
    ): ?string {
        [$fq_class_name, $property_name] = explode('::$', $property_id);

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
        string $property_id,
        bool $read_mode,
        ?StatementsSource $source = null,
    ): ?string {
        [$fq_class_name, $property_name] = explode('::$', $property_id);

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
            $appearing_property_id = $class_storage->appearing_property_ids[$property_name];

            return explode('::$', $appearing_property_id)[0];
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStorage(string $property_id): PropertyStorage
    {
        // remove leading backslash if it exists
        $property_id = ltrim($property_id, '\\');

        [$fq_class_name, $property_name] = explode('::$', $property_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

            if (isset($declaring_class_storage->properties[$property_name])) {
                return $declaring_class_storage->properties[$property_name];
            }
        }

        throw new UnexpectedValueException('Property ' . $property_id . ' should exist');
    }

    /**
     * @psalm-mutation-free
     */
    public function hasStorage(string $property_id): bool
    {
        // remove leading backslash if it exists
        $property_id = ltrim($property_id, '\\');

        [$fq_class_name, $property_name] = explode('::$', $property_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

            return isset($declaring_class_storage->properties[$property_name]);
        }
        return false;
    }

    public function getPropertyType(
        string $property_id,
        bool $property_set,
        ?StatementsSource $source = null,
        ?Context $context = null,
    ): ?Union {
        // remove leading backslash if it exists
        $property_id = ltrim($property_id, '\\');

        [$fq_class_name, $property_name] = explode('::$', $property_id);

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
                throw new UnexpectedValueException('Property ' . $property_id . ' should exist');
            }
        } else {
            throw new UnexpectedValueException('Property ' . $property_id . ' should exist');
        }

        if ($storage->type) {
            if ($property_set) {
                if (isset($class_storage->pseudo_property_set_types['$'.$property_name])) {
                    return $class_storage->pseudo_property_set_types['$'.$property_name];
                }
                $setter_param_type = $storage->hook_set?->param_type;
                // a write from outside the hooks goes through the set hook, one inside them sets the backing value
                if ($setter_param_type !== null
                    && ($declaring_class_storage->is_interface
                        || !self::isInHookOf($source, $declaring_class_storage->name, $property_name))
                ) {
                    return $setter_param_type;
                }
            } else {
                if (isset($class_storage->pseudo_property_get_types['$'.$property_name])) {
                    return $class_storage->pseudo_property_get_types['$'.$property_name];
                }
            }

            return $storage->type;
        }

        if (!isset($class_storage->overridden_property_ids[$property_name])) {
            return null;
        }

        foreach ($class_storage->overridden_property_ids[$property_name] as $overridden_property_id) {
            $overridden_storage = $this->getStorage($overridden_property_id);

            if ($overridden_storage->type) {
                return $overridden_storage->type;
            }
        }

        return null;
    }

    /**
     * Whether the code is in a hook of the property
     */
    public static function isInHookOf(?StatementsSource $source, string $class_name, string $property_name): bool
    {
        while ($source !== null) {
            if ($source instanceof ClosureAnalyzer
                && ($hook_id = $source->getPropertyHookId()) !== null
            ) {
                // the hook of a trait's property is analysed for the class using the trait
                $hook_class = explode('::$', $hook_id, 2)[0];
                $hook_property_id = $hook_class . '::$' . $property_name;
                $declaring_class = $source->getCodebase()->properties->getDeclaringClassForProperty(
                    $hook_property_id,
                    true,
                );

                return $declaring_class !== null
                    && strtolower($declaring_class) === strtolower($class_name)
                    && str_starts_with(strtolower($hook_id), strtolower($hook_property_id . '::'));
            }

            $parent_source = $source->getSource();

            if ($parent_source === $source) {
                return false;
            }

            $source = $parent_source;
        }

        return false;
    }
}
