<?php
namespace Psalm\Codebase;

use Psalm\CodeLocation;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Provider\FileReferenceProvider;

/**
 * @internal
 *
 * Handles information about class properties
 */
class Properties
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    public function __construct(
        ClassLikeStorageProvider $storage_provider,
        FileReferenceProvider $file_reference_provider
    ) {
        $this->classlike_storage_provider = $storage_provider;
        $this->file_reference_provider = $file_reference_provider;
    }

    /**
     * Whether or not a given property exists
     *
     * @param  string $property_id
     * @param  ?string $calling_method_id
     * @param  string $calling_method_id
     *
     * @return bool
     */
    public function propertyExists(
        $property_id,
        $calling_method_id = null,
        CodeLocation $code_location = null
    ) {
        // remove trailing backslash if it exists
        $property_id = preg_replace('/^\\\\/', '', $property_id);

        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];

            if ($calling_method_id) {
                $this->file_reference_provider->addReferenceToClassMethod(
                    $calling_method_id,
                    strtolower($declaring_property_class) . '::$' . $property_name
                );
            }

            if ($this->collect_references && $code_location) {
                $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);
                $declaring_property_storage = $declaring_class_storage->properties[$property_name];

                if ($declaring_property_storage->referencing_locations === null) {
                    $declaring_property_storage->referencing_locations = [];
                }

                $declaring_property_storage->referencing_locations[$code_location->file_path][] = $code_location;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  string $property_id
     *
     * @return string|null
     */
    public function getDeclaringClassForProperty($property_id)
    {
        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $fq_class_name = strtolower($fq_class_name);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            return $class_storage->declaring_property_ids[$property_name];
        }
    }

    /**
     * Get the class this property appears in (vs is declared in, which could give a trait)
     *
     * @param  string $property_id
     *
     * @return string|null
     */
    public function getAppearingClassForProperty($property_id)
    {
        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $fq_class_name = strtolower($fq_class_name);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->appearing_property_ids[$property_name])) {
            $appearing_property_id = $class_storage->appearing_property_ids[$property_name];

            return explode('::$', $appearing_property_id)[0];
        }
    }

    /**
     * @param  string $property_id
     * @return  \Psalm\Storage\PropertyStorage
     */
    public function getStorage($property_id)
    {
        // remove trailing backslash if it exists
        $property_id = preg_replace('/^\\\\/', '', $property_id);

        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

            if (isset($declaring_class_storage->properties[$property_name])) {
                return $declaring_class_storage->properties[$property_name];
            }
        }

        throw new \UnexpectedValueException('Property ' . $property_id . ' should exist');
    }
}
