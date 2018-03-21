<?php
namespace Psalm\Codebase;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Config;
use Psalm\Issue\CircularReference;
use Psalm\IssueBuffer;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;

/**
 * @internal
 *
 * Populates file and class information so that analysis can work properly
 */
class Populator
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var FileStorageProvider
     */
    private $file_storage_provider;

    /**
     * @var bool
     */
    private $debug_output;

    /**
     * @var ClassLikes
     */
    private $classlikes;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param bool $debug_output
     */
    public function __construct(
        Config $config,
        ClassLikeStorageProvider $classlike_storage_provider,
        FileStorageProvider $file_storage_provider,
        ClassLikes $classlikes,
        $debug_output
    ) {
        $this->classlike_storage_provider = $classlike_storage_provider;
        $this->file_storage_provider = $file_storage_provider;
        $this->classlikes = $classlikes;
        $this->debug_output = $debug_output;
        $this->config = $config;
    }

    /**
     * @return void
     */
    public function populateCodebase()
    {
        if ($this->debug_output) {
            echo 'ClassLikeStorage is populating' . PHP_EOL;
        }

        foreach ($this->classlike_storage_provider->getAll() as $class_storage) {
            if (!$class_storage->user_defined && !$class_storage->stubbed) {
                continue;
            }

            $this->populateClassLikeStorage($class_storage);
        }

        if ($this->debug_output) {
            echo 'ClassLikeStorage is populated' . PHP_EOL;
        }

        if ($this->debug_output) {
            echo 'FileStorage is populating' . PHP_EOL;
        }

        $all_file_storage = $this->file_storage_provider->getAll();

        foreach ($all_file_storage as $file_storage) {
            $this->populateFileStorage($file_storage);
        }

        if ($this->config->allow_phpstorm_generics) {
            foreach ($this->classlike_storage_provider->getAll() as $class_storage) {
                foreach ($class_storage->properties as $property_storage) {
                    if ($property_storage->type) {
                        $this->convertPhpStormGenericToPsalmGeneric($property_storage->type, true);
                    }
                }

                foreach ($class_storage->methods as $method_storage) {
                    if ($method_storage->return_type) {
                        $this->convertPhpStormGenericToPsalmGeneric($method_storage->return_type);
                    }

                    foreach ($method_storage->params as $param_storage) {
                        if ($param_storage->type) {
                            $this->convertPhpStormGenericToPsalmGeneric($param_storage->type);
                        }
                    }
                }
            }

            foreach ($all_file_storage as $file_storage) {
                foreach ($file_storage->functions as $function_storage) {
                    if ($function_storage->return_type) {
                        $this->convertPhpStormGenericToPsalmGeneric($function_storage->return_type);
                    }

                    foreach ($function_storage->params as $param_storage) {
                        if ($param_storage->type) {
                            $this->convertPhpStormGenericToPsalmGeneric($param_storage->type);
                        }
                    }
                }
            }
        }

        if ($this->debug_output) {
            echo 'FileStorage is populated' . PHP_EOL;
        }
    }

    /**
     * @param  ClassLikeStorage $storage
     * @param  array            $dependent_classlikes
     *
     * @return void
     */
    private function populateClassLikeStorage(ClassLikeStorage $storage, array $dependent_classlikes = [])
    {
        if ($storage->populated) {
            return;
        }

        $fq_classlike_name_lc = strtolower($storage->name);

        if (isset($dependent_classlikes[$fq_classlike_name_lc])) {
            if ($storage->location && IssueBuffer::accepts(
                new CircularReference(
                    'Circular reference discovered when loading ' . $storage->name,
                    $storage->location
                )
            )) {
                // fall through
            }

            return;
        }

        $storage_provider = $this->classlike_storage_provider;

        $this->populateDataFromTraits($storage, $storage_provider, $dependent_classlikes);

        $dependent_classlikes[$fq_classlike_name_lc] = true;

        if ($storage->parent_classes) {
            $this->populateDataFromParentClass($storage, $storage_provider, $dependent_classlikes);
        }

        $this->populateInterfaceDataFromParentInterfaces($storage, $storage_provider, $dependent_classlikes);

        $this->populateDataFromImplementedInterfaces($storage, $storage_provider, $dependent_classlikes);

        if ($storage->location) {
            $file_path = $storage->location->file_path;

            foreach ($storage->parent_interfaces as $parent_interface_lc) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, $parent_interface_lc);
            }

            foreach ($storage->parent_classes as $parent_class_lc) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, $parent_class_lc);
            }

            foreach ($storage->class_implements as $implemented_interface) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, strtolower($implemented_interface));
            }

            foreach ($storage->used_traits as $used_trait_lc) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, $used_trait_lc);
            }
        }

        if ($this->debug_output) {
            echo 'Have populated ' . $storage->name . PHP_EOL;
        }

        $storage->populated = true;
    }

    /**
     * @return void
     */
    private function populateDataFromTraits(
        ClassLikeStorage $storage,
        ClassLikeStorageProvider $storage_provider,
        array $dependent_classlikes
    ) {
        foreach ($storage->used_traits as $used_trait_lc => $_) {
            try {
                $trait_storage = $storage_provider->get($used_trait_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateClassLikeStorage($trait_storage, $dependent_classlikes);

            $this->inheritMethodsFromParent($storage, $trait_storage);
            $this->inheritPropertiesFromParent($storage, $trait_storage);
        }
    }

    /**
     * @return void
     */
    private function populateDataFromParentClass(
        ClassLikeStorage $storage,
        ClassLikeStorageProvider $storage_provider,
        array $dependent_classlikes
    ) {
        $parent_storage_class = reset($storage->parent_classes);

        try {
            $parent_storage = $storage_provider->get($parent_storage_class);
        } catch (\InvalidArgumentException $e) {
            $storage->invalid_dependencies[] = $parent_storage_class;
            $parent_storage = null;
        }

        if ($parent_storage) {
            $this->populateClassLikeStorage($parent_storage, $dependent_classlikes);

            $storage->parent_classes = array_merge($storage->parent_classes, $parent_storage->parent_classes);

            $this->inheritMethodsFromParent($storage, $parent_storage);
            $this->inheritPropertiesFromParent($storage, $parent_storage);

            $storage->class_implements += $parent_storage->class_implements;
            $storage->invalid_dependencies = array_merge(
                $storage->invalid_dependencies,
                $parent_storage->invalid_dependencies
            );

            $storage->public_class_constants += $parent_storage->public_class_constants;
            $storage->protected_class_constants += $parent_storage->protected_class_constants;
        }
    }

    /**
     * @return void
     */
    private function populateInterfaceDataFromParentInterfaces(
        ClassLikeStorage $storage,
        ClassLikeStorageProvider $storage_provider,
        array $dependent_classlikes
    ) {
        $parent_interfaces = [];

        foreach ($storage->parent_interfaces as $parent_interface_lc => $_) {
            try {
                $parent_interface_storage = $storage_provider->get($parent_interface_lc);
            } catch (\InvalidArgumentException $e) {
                $storage->invalid_dependencies[] = $parent_interface_lc;
                continue;
            }

            $this->populateClassLikeStorage($parent_interface_storage, $dependent_classlikes);

            // copy over any constants
            $storage->public_class_constants = array_merge(
                $storage->public_class_constants,
                $parent_interface_storage->public_class_constants
            );

            $storage->invalid_dependencies = array_merge(
                $storage->invalid_dependencies,
                $parent_interface_storage->invalid_dependencies
            );

            $parent_interfaces = array_merge($parent_interfaces, $parent_interface_storage->parent_interfaces);

            $this->inheritMethodsFromParent($storage, $parent_interface_storage);
        }

        $storage->parent_interfaces = array_merge($parent_interfaces, $storage->parent_interfaces);
    }

    /**
     * @return void
     */
    private function populateDataFromImplementedInterfaces(
        ClassLikeStorage $storage,
        ClassLikeStorageProvider $storage_provider,
        array $dependent_classlikes
    ) {
        $extra_interfaces = [];

        foreach ($storage->class_implements as $implemented_interface_lc => $_) {
            try {
                $implemented_interface_storage = $storage_provider->get($implemented_interface_lc);
            } catch (\InvalidArgumentException $e) {
                $storage->invalid_dependencies[] = $implemented_interface_lc;
                continue;
            }

            $this->populateClassLikeStorage($implemented_interface_storage, $dependent_classlikes);

            // copy over any constants
            $storage->public_class_constants = array_merge(
                $storage->public_class_constants,
                $implemented_interface_storage->public_class_constants
            );

            $storage->invalid_dependencies = array_merge(
                $storage->invalid_dependencies,
                $implemented_interface_storage->invalid_dependencies
            );

            $extra_interfaces = array_merge($extra_interfaces, $implemented_interface_storage->parent_interfaces);

            $storage->public_class_constants += $implemented_interface_storage->public_class_constants;
        }

        $storage->class_implements = array_merge($extra_interfaces, $storage->class_implements);

        $interface_method_implementers = [];

        foreach ($storage->class_implements as $implemented_interface) {
            try {
                $implemented_interface_storage = $storage_provider->get($implemented_interface);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            foreach ($implemented_interface_storage->methods as $method_name => $method) {
                if ($method->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    $mentioned_method_id = $implemented_interface . '::' . $method_name;
                    $interface_method_implementers[$method_name][] = $mentioned_method_id;
                }
            }
        }

        foreach ($interface_method_implementers as $method_name => $interface_method_ids) {
            if (count($interface_method_ids) === 1) {
                $storage->overridden_method_ids[$method_name][] = $interface_method_ids[0];
            } else {
                $storage->interface_method_ids[$method_name] = $interface_method_ids;
            }
        }
    }

    /**
     * @param  FileStorage $storage
     * @param  array<string, bool> $dependent_file_paths
     *
     * @return void
     */
    private function populateFileStorage(FileStorage $storage, array $dependent_file_paths = [])
    {
        if ($storage->populated) {
            return;
        }

        $file_path_lc = strtolower($storage->file_path);

        if (isset($dependent_file_paths[$file_path_lc])) {
            return;
        }

        $dependent_file_paths[$file_path_lc] = true;

        foreach ($storage->included_file_paths as $included_file_path => $_) {
            try {
                $included_file_storage = $this->file_storage_provider->get($included_file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateFileStorage($included_file_storage, $dependent_file_paths);

            $storage->declaring_function_ids = array_merge(
                $included_file_storage->declaring_function_ids,
                $storage->declaring_function_ids
            );

            $storage->declaring_constants = array_merge(
                $included_file_storage->declaring_constants,
                $storage->declaring_constants
            );
        }

        $storage->populated = true;
    }

    /**
     * @param  Type\Union $candidate
     * @param  bool       $is_property
     *
     * @return void
     */
    private function convertPhpStormGenericToPsalmGeneric(Type\Union $candidate, $is_property = false)
    {
        $atomic_types = $candidate->getTypes();

        if (isset($atomic_types['array']) && count($atomic_types) > 1) {
            $iterator_name = null;
            $generic_params = null;

            foreach ($atomic_types as $type) {
                if ($type instanceof Type\Atomic\TNamedObject
                    && (!$type->from_docblock || $is_property)
                    && (
                        strtolower($type->value) === 'traversable'
                        || $this->classlikes->interfaceExtends(
                            $type->value,
                            'Traversable'
                        )
                        || $this->classlikes->classImplements(
                            $type->value,
                            'Traversable'
                        )
                    )
                ) {
                    $iterator_name = $type->value;
                } elseif ($type instanceof Type\Atomic\TArray) {
                    $generic_params = $type->type_params;
                }
            }

            if ($iterator_name && $generic_params) {
                $generic_iterator = new Type\Atomic\TGenericObject($iterator_name, $generic_params);
                $candidate->removeType('array');
                $candidate->addType($generic_iterator);
            }
        }
    }

    /**
     * @param ClassLikeStorage $storage
     * @param ClassLikeStorage $parent_storage
     *
     * @return void
     */
    protected function inheritMethodsFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
    {
        $fq_class_name = $storage->name;

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $aliased_method_names = [$method_name];

            if ($parent_storage->is_trait
                && $storage->trait_alias_map
                && isset($storage->trait_alias_map[$method_name])
            ) {
                $aliased_method_names[] = $storage->trait_alias_map[$method_name];
            }

            foreach ($aliased_method_names as $aliased_method_name) {
                if (isset($storage->appearing_method_ids[$aliased_method_name])) {
                    continue;
                }

                $implemented_method_id = $fq_class_name . '::' . $aliased_method_name;

                $storage->appearing_method_ids[$aliased_method_name] =
                    $parent_storage->is_trait ? $implemented_method_id : $appearing_method_id;
            }
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_method_ids as $method_name => $declaring_method_id) {
            if (!$parent_storage->is_trait) {
                $storage->overridden_method_ids[$method_name][] = $declaring_method_id;
            }

            $aliased_method_names = [$method_name];

            if ($parent_storage->is_trait
                && $storage->trait_alias_map
                && isset($storage->trait_alias_map[$method_name])
            ) {
                $aliased_method_names[] = $storage->trait_alias_map[$method_name];
            }

            foreach ($aliased_method_names as $aliased_method_name) {
                if (isset($storage->declaring_method_ids[$aliased_method_name])) {
                    list($implementing_fq_class_name, $implementing_method_name) = explode(
                        '::',
                        $storage->declaring_method_ids[$aliased_method_name]
                    );

                    $implementing_class_storage = $this->classlike_storage_provider->get($implementing_fq_class_name);

                    if (!$implementing_class_storage->methods[$implementing_method_name]->abstract) {
                        continue;
                    }
                }

                $storage->declaring_method_ids[$aliased_method_name] = $declaring_method_id;
                $storage->inheritable_method_ids[$aliased_method_name] = $declaring_method_id;
            }
        }
    }

    /**
     * @param ClassLikeStorage $storage
     * @param ClassLikeStorage $parent_storage
     *
     * @return void
     */
    private function inheritPropertiesFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
    {
        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (isset($storage->appearing_property_ids[$property_name])) {
                continue;
            }

            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $implemented_property_id = $storage->name . '::$' . $property_name;

            $storage->appearing_property_ids[$property_name] =
                $parent_storage->is_trait ? $implemented_property_id : $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_id) {
            if (isset($storage->declaring_property_ids[$property_name])) {
                continue;
            }

            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = $declaring_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            if (!$parent_storage->is_trait) {
                $storage->overridden_property_ids[$property_name][] = $inheritable_property_id;
            }

            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }
}
