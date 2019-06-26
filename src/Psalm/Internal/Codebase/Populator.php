<?php
namespace Psalm\Internal\Codebase;

use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Config;
use Psalm\Issue\CircularReference;
use Psalm\IssueBuffer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Progress\Progress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use function strtolower;
use function strpos;
use function explode;
use function count;
use function array_keys;
use function is_int;
use function array_merge;
use function reset;

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
     * @var Progress
     */
    private $progress;

    /**
     * @var ClassLikes
     */
    private $classlikes;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var FileReferenceProvider
     */
    private $file_reference_provider;

    public function __construct(
        Config $config,
        ClassLikeStorageProvider $classlike_storage_provider,
        FileStorageProvider $file_storage_provider,
        ClassLikes $classlikes,
        FileReferenceProvider $file_reference_provider,
        Progress $progress
    ) {
        $this->classlike_storage_provider = $classlike_storage_provider;
        $this->file_storage_provider = $file_storage_provider;
        $this->classlikes = $classlikes;
        $this->progress = $progress;
        $this->config = $config;
        $this->file_reference_provider = $file_reference_provider;
    }

    /**
     * @return void
     */
    public function populateCodebase(\Psalm\Codebase $codebase)
    {
        $this->progress->debug('ClassLikeStorage is populating' . "\n");

        foreach ($this->classlike_storage_provider->getNew() as $class_storage) {
            $this->populateClassLikeStorage($class_storage);
        }

        $this->progress->debug('ClassLikeStorage is populated' . "\n");

        $this->progress->debug('FileStorage is populating' . "\n");

        $all_file_storage = $this->file_storage_provider->getNew();

        foreach ($all_file_storage as $file_storage) {
            $this->populateFileStorage($file_storage);
        }

        foreach ($this->classlike_storage_provider->getNew() as $class_storage) {
            if ($this->config->allow_phpstorm_generics) {
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

            if ($class_storage->aliases) {
                foreach ($class_storage->public_class_constant_nodes as $const_name => $node) {
                    $const_type = \Psalm\Internal\Analyzer\StatementsAnalyzer::getSimpleType(
                        $codebase,
                        $node,
                        $class_storage->aliases,
                        null,
                        null,
                        $class_storage->name
                    );

                    $class_storage->public_class_constants[$const_name] = $const_type ?: Type::getMixed();
                }

                foreach ($class_storage->protected_class_constant_nodes as $const_name => $node) {
                    $const_type = \Psalm\Internal\Analyzer\StatementsAnalyzer::getSimpleType(
                        $codebase,
                        $node,
                        $class_storage->aliases,
                        null,
                        null,
                        $class_storage->name
                    );

                    $class_storage->protected_class_constants[$const_name] = $const_type ?: Type::getMixed();
                }

                foreach ($class_storage->private_class_constant_nodes as $const_name => $node) {
                    $const_type = \Psalm\Internal\Analyzer\StatementsAnalyzer::getSimpleType(
                        $codebase,
                        $node,
                        $class_storage->aliases,
                        null,
                        null,
                        $class_storage->name
                    );

                    $class_storage->private_class_constants[$const_name] = $const_type ?: Type::getMixed();
                }
            }
        }

        if ($this->config->allow_phpstorm_generics) {
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

        $this->progress->debug('FileStorage is populated' . "\n");

        $this->classlike_storage_provider->populated();
        $this->file_storage_provider->populated();
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

        if (!strpos($fq_classlike_name_lc, '\\')
            && !isset($storage->methods['__construct'])
            && isset($storage->methods[$fq_classlike_name_lc])
            && !$storage->is_interface
            && !$storage->is_trait
        ) {
            $storage->methods['__construct'] = $storage->methods[$fq_classlike_name_lc];
        }

        $this->populateInterfaceDataFromParentInterfaces($storage, $storage_provider, $dependent_classlikes);

        $this->populateDataFromImplementedInterfaces($storage, $storage_provider, $dependent_classlikes);

        if ($storage->location) {
            $file_path = $storage->location->file_path;

            foreach ($storage->parent_interfaces as $parent_interface_lc) {
                $this->file_reference_provider->addFileInheritanceToClass($file_path, $parent_interface_lc);
            }

            foreach ($storage->parent_classes as $parent_class_lc => $_) {
                $this->file_reference_provider->addFileInheritanceToClass($file_path, $parent_class_lc);
            }

            foreach ($storage->class_implements as $implemented_interface) {
                $this->file_reference_provider->addFileInheritanceToClass(
                    $file_path,
                    strtolower($implemented_interface)
                );
            }

            foreach ($storage->used_traits as $used_trait_lc => $_) {
                $this->file_reference_provider->addFileInheritanceToClass($file_path, $used_trait_lc);
            }
        }

        if ($storage->internal
            && !$storage->is_interface
            && !$storage->is_trait
        ) {
            foreach ($storage->methods as $method) {
                $method->internal = true;
            }

            foreach ($storage->properties as $property) {
                $property->internal = true;
            }
        }

        $this->populateOverriddenMethods($storage);

        $this->progress->debug('Have populated ' . $storage->name . "\n");

        $storage->populated = true;
    }

    /** @return void */
    private function populateOverriddenMethods(
        ClassLikeStorage $storage
    ) {
        foreach ($storage->methods as $method_name => $method_storage) {
            if (isset($storage->overridden_method_ids[$method_name])) {
                foreach ($storage->overridden_method_ids[$method_name] as $declaring_method_id) {
                    list($declaring_class, $declaring_method_name) = explode('::', $declaring_method_id);
                    $declaring_class_storage = $this->classlike_storage_provider->get($declaring_class);

                    $declaring_method_storage = $declaring_class_storage->methods[strtolower($declaring_method_name)];

                    if ($declaring_method_storage->has_docblock_param_types
                        && !$method_storage->has_docblock_param_types
                        && !isset($storage->documenting_method_ids[$method_name])
                    ) {
                        $storage->documenting_method_ids[$method_name] = $declaring_method_id;
                    }

                    // tell the declaring class it's overridden downstream
                    $declaring_method_storage->overridden_downstream = true;
                    $declaring_method_storage->overridden_somewhere = true;

                    if (!$method_storage->throws
                        && $method_storage->inheritdoc
                        && $declaring_method_storage->throws
                    ) {
                    }

                    if (count($storage->overridden_method_ids[$method_name]) === 1
                        && $method_storage->signature_return_type
                        && !$method_storage->signature_return_type->isVoid()
                        && ($method_storage->return_type === $method_storage->signature_return_type
                            || $method_storage->inherited_return_type)
                    ) {
                        if (isset($declaring_class_storage->methods[$method_name])) {
                            $declaring_method_storage = $declaring_class_storage->methods[$method_name];

                            if ($declaring_method_storage->return_type
                                && $declaring_method_storage->return_type
                                    !== $declaring_method_storage->signature_return_type
                            ) {
                                if ($declaring_method_storage->signature_return_type) {
                                    $method_storage->return_type = $declaring_method_storage->return_type;
                                    $method_storage->inherited_return_type = true;
                                } elseif (TypeAnalyzer::isSimplyContainedBy(
                                    $declaring_method_storage->return_type,
                                    $method_storage->signature_return_type
                                )) {
                                    $method_storage->return_type = $declaring_method_storage->return_type;
                                    $method_storage->inherited_return_type = true;
                                }
                            }
                        }
                    }
                }
            }
        }
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
                $used_trait_lc = $this->classlikes->getUnAliasedName(
                    $used_trait_lc
                );
                $trait_storage = $storage_provider->get($used_trait_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateClassLikeStorage($trait_storage, $dependent_classlikes);

            $this->inheritMethodsFromParent($storage, $trait_storage);
            $this->inheritPropertiesFromParent($storage, $trait_storage);

            if ($trait_storage->template_types) {
                if (isset($storage->template_type_extends[$trait_storage->name])) {
                    foreach ($storage->template_type_extends[$trait_storage->name] as $i => $type) {
                        $trait_template_type_names = array_keys($trait_storage->template_types);

                        $mapped_name = $trait_template_type_names[$i] ?? null;

                        if ($mapped_name) {
                            $storage->template_type_extends[$trait_storage->name][$mapped_name] = $type;
                        }
                    }

                    if ($trait_storage->template_type_extends) {
                        foreach ($trait_storage->template_type_extends as $t_storage_class => $type_map) {
                            foreach ($type_map as $i => $type) {
                                if (isset($storage->template_type_extends[$t_storage_class][$i])
                                    || is_int($i)
                                ) {
                                    continue;
                                }

                                $storage->template_type_extends[$t_storage_class][$i] = self::extendType(
                                    $type,
                                    $storage
                                );
                            }
                        }
                    }
                } else {
                    $storage->template_type_extends[$trait_storage->name] = [];

                    foreach ($trait_storage->template_types as $template_name => $template_type_map) {
                        foreach ($template_type_map as $template_type) {
                            $storage->template_type_extends[$trait_storage->name][$template_name]
                                = $template_type[0];
                        }
                    }
                }
            } elseif ($trait_storage->template_type_extends) {
                $storage->template_type_extends = array_merge(
                    $storage->template_type_extends ?: [],
                    $trait_storage->template_type_extends
                );
            }
        }
    }

    private static function extendType(
        Type\Union $type,
        ClassLikeStorage $storage
    ) : Type\Union {
        $extended_types = [];

        foreach ($type->getTypes() as $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TTemplateParam
                && $atomic_type->defining_class
            ) {
                $referenced_type
                    = $storage->template_type_extends[$atomic_type->defining_class][$atomic_type->param_name]
                        ?? null;

                if ($referenced_type) {
                    foreach ($referenced_type->getTypes() as $atomic_referenced_type) {
                        if (!$atomic_referenced_type instanceof Type\Atomic\TTemplateParam) {
                            $extended_types[] = $atomic_referenced_type;
                        } else {
                            $extended_types[] = $atomic_type;
                        }
                    }
                } else {
                    $extended_types[] = $atomic_type;
                }
            } else {
                $extended_types[] = $atomic_type;
            }
        }

        return new Type\Union($extended_types);
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
            $parent_storage_class = $this->classlikes->getUnAliasedName(
                strtolower($parent_storage_class)
            );
            $parent_storage = $storage_provider->get($parent_storage_class);
        } catch (\InvalidArgumentException $e) {
            $this->progress->debug('Populator could not find dependency (' . __LINE__ . ")\n");

            $storage->invalid_dependencies[] = $parent_storage_class;
            $parent_storage = null;
        }

        if ($parent_storage && $parent_storage_class) {
            $this->populateClassLikeStorage($parent_storage, $dependent_classlikes);

            $storage->parent_classes = array_merge($storage->parent_classes, $parent_storage->parent_classes);

            if ($parent_storage->template_types) {
                if (isset($storage->template_type_extends[$parent_storage->name])) {
                    foreach ($storage->template_type_extends[$parent_storage->name] as $i => $type) {
                        $parent_template_type_names = array_keys($parent_storage->template_types);

                        $mapped_name = $parent_template_type_names[$i] ?? null;

                        if ($mapped_name) {
                            $storage->template_type_extends[$parent_storage->name][$mapped_name] = $type;
                        }
                    }

                    if ($parent_storage->template_type_extends) {
                        foreach ($parent_storage->template_type_extends as $t_storage_class => $type_map) {
                            foreach ($type_map as $i => $type) {
                                if (isset($storage->template_type_extends[$t_storage_class][$i])
                                    || is_int($i)
                                ) {
                                    continue;
                                }

                                $storage->template_type_extends[$t_storage_class][$i] = self::extendType(
                                    $type,
                                    $storage
                                );
                            }
                        }
                    }
                } else {
                    $storage->template_type_extends[$parent_storage->name] = [];

                    foreach ($parent_storage->template_types as $template_name => $template_type_map) {
                        foreach ($template_type_map as $template_type) {
                            $storage->template_type_extends[$parent_storage->name][$template_name]
                                = $template_type[0];
                        }
                    }

                    if ($parent_storage->template_type_extends) {
                        $storage->template_type_extends = array_merge(
                            $storage->template_type_extends,
                            $parent_storage->template_type_extends
                        );
                    }
                }
            } elseif ($parent_storage->template_type_extends) {
                $storage->template_type_extends = array_merge(
                    $storage->template_type_extends ?: [],
                    $parent_storage->template_type_extends
                );
            }

            $this->inheritMethodsFromParent($storage, $parent_storage);
            $this->inheritPropertiesFromParent($storage, $parent_storage);

            $storage->class_implements = array_merge($storage->class_implements, $parent_storage->class_implements);
            $storage->invalid_dependencies = array_merge(
                $storage->invalid_dependencies,
                $parent_storage->invalid_dependencies
            );

            if ($parent_storage->has_visitor_issues) {
                $storage->has_visitor_issues = true;
            }

            $storage->public_class_constants = array_merge(
                $parent_storage->public_class_constants,
                $storage->public_class_constants
            );
            $storage->protected_class_constants = array_merge(
                $parent_storage->protected_class_constants,
                $storage->protected_class_constants
            );

            foreach ($parent_storage->public_class_constant_nodes as $name => $_) {
                $storage->public_class_constants[$name] = Type::getMixed();
            }

            foreach ($parent_storage->protected_class_constant_nodes as $name => $_) {
                $storage->protected_class_constants[$name] = Type::getMixed();
            }

            $storage->pseudo_property_get_types += $parent_storage->pseudo_property_get_types;
            $storage->pseudo_property_set_types += $parent_storage->pseudo_property_set_types;

            $parent_storage->dependent_classlikes[strtolower($storage->name)] = true;

            $storage->pseudo_methods += $parent_storage->pseudo_methods;
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
                $parent_interface_lc = $this->classlikes->getUnAliasedName(
                    $parent_interface_lc
                );
                $parent_interface_storage = $storage_provider->get($parent_interface_lc);
            } catch (\InvalidArgumentException $e) {
                $this->progress->debug('Populator could not find dependency (' . __LINE__ . ")\n");

                $storage->invalid_dependencies[] = $parent_interface_lc;
                continue;
            }

            $this->populateClassLikeStorage($parent_interface_storage, $dependent_classlikes);

            // copy over any constants
            $storage->public_class_constants = array_merge(
                $parent_interface_storage->public_class_constants,
                $storage->public_class_constants
            );

            $storage->invalid_dependencies = array_merge(
                $storage->invalid_dependencies,
                $parent_interface_storage->invalid_dependencies
            );

            foreach ($parent_interface_storage->public_class_constant_nodes as $name => $_) {
                $storage->public_class_constants[$name] = Type::getMixed();
            }

            if ($parent_interface_storage->template_types) {
                if (isset($storage->template_type_extends[$parent_interface_storage->name])) {
                    foreach ($storage->template_type_extends[$parent_interface_storage->name] as $i => $type) {
                        $parent_template_type_names = array_keys($parent_interface_storage->template_types);

                        $mapped_name = $parent_template_type_names[$i] ?? null;

                        if ($mapped_name) {
                            $storage->template_type_extends[$parent_interface_storage->name][$mapped_name] = $type;
                        }
                    }

                    if ($parent_interface_storage->template_type_extends) {
                        foreach ($parent_interface_storage->template_type_extends as $t_storage_class => $type_map) {
                            foreach ($type_map as $i => $type) {
                                if (isset($storage->template_type_extends[$t_storage_class][$i])
                                    || is_int($i)
                                ) {
                                    continue;
                                }

                                $storage->template_type_extends[$t_storage_class][$i] = self::extendType(
                                    $type,
                                    $storage
                                );
                            }
                        }
                    }
                } else {
                    $storage->template_type_extends[$parent_interface_storage->name] = [];

                    foreach ($parent_interface_storage->template_types as $template_name => $template_type_map) {
                        foreach ($template_type_map as $template_type) {
                            $storage->template_type_extends[$parent_interface_storage->name][$template_name]
                                = $template_type[0];
                        }
                    }
                }
            }

            $parent_interface_storage->dependent_classlikes[strtolower($storage->name)] = true;

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
                $implemented_interface_lc = $this->classlikes->getUnAliasedName(
                    strtolower($implemented_interface_lc)
                );
                $implemented_interface_storage = $storage_provider->get($implemented_interface_lc);
            } catch (\InvalidArgumentException $e) {
                $this->progress->debug('Populator could not find dependency (' . __LINE__ . ")\n");

                $storage->invalid_dependencies[] = $implemented_interface_lc;
                continue;
            }

            $this->populateClassLikeStorage($implemented_interface_storage, $dependent_classlikes);

            // copy over any constants
            $storage->public_class_constants = array_merge(
                $implemented_interface_storage->public_class_constants,
                $storage->public_class_constants
            );

            foreach ($implemented_interface_storage->public_class_constant_nodes as $name => $_) {
                $storage->public_class_constants[$name] = Type::getMixed();
            }

            $storage->invalid_dependencies = array_merge(
                $storage->invalid_dependencies,
                $implemented_interface_storage->invalid_dependencies
            );

            if ($implemented_interface_storage->template_types) {
                if (isset($storage->template_type_extends[$implemented_interface_storage->name])) {
                    foreach ($storage->template_type_extends[$implemented_interface_storage->name] as $i => $type) {
                        $parent_template_type_names = array_keys($implemented_interface_storage->template_types);

                        $mapped_name = $parent_template_type_names[$i] ?? null;

                        if ($mapped_name) {
                            $storage->template_type_extends[$implemented_interface_storage->name][$mapped_name] = $type;
                        }
                    }

                    if ($implemented_interface_storage->template_type_extends) {
                        foreach ($implemented_interface_storage->template_type_extends as $e_i => $type) {
                            if (isset($storage->template_type_extends[$e_i])) {
                                continue;
                            }

                            $storage->template_type_extends[$e_i] = $type;
                        }
                    }
                } else {
                    $storage->template_type_extends[$implemented_interface_storage->name] = [];

                    foreach ($implemented_interface_storage->template_types as $template_name => $template_type_map) {
                        foreach ($template_type_map as $template_type) {
                            $storage->template_type_extends[$implemented_interface_storage->name][$template_name]
                                = $template_type[0];
                        }
                    }
                }
            } elseif ($implemented_interface_storage->template_type_extends) {
                $storage->template_type_extends = array_merge(
                    $storage->template_type_extends ?: [],
                    $implemented_interface_storage->template_type_extends
                );
            }

            $extra_interfaces = array_merge($extra_interfaces, $implemented_interface_storage->parent_interfaces);
        }

        $storage->class_implements = array_merge($storage->class_implements, $extra_interfaces);

        $interface_method_implementers = [];

        foreach ($storage->class_implements as $implemented_interface_lc => $_) {
            try {
                $implemented_interface = $this->classlikes->getUnAliasedName(
                    $implemented_interface_lc
                );
                $implemented_interface_storage = $storage_provider->get($implemented_interface);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $implemented_interface_storage->dependent_classlikes[strtolower($storage->name)] = true;

            foreach ($implemented_interface_storage->methods as $method_name => $method) {
                if ($method->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC) {
                    $mentioned_method_id = $implemented_interface . '::' . $method_name;
                    $interface_method_implementers[$method_name][] = $mentioned_method_id;
                }
            }
        }

        foreach ($interface_method_implementers as $method_name => $interface_method_ids) {
            if (count($interface_method_ids) === 1) {
                if (isset($storage->methods[$method_name])) {
                    $method_storage = $storage->methods[$method_name];

                    if ($method_storage->signature_return_type
                        && !$method_storage->signature_return_type->isVoid()
                        && $method_storage->return_type === $method_storage->signature_return_type
                    ) {
                        list($interface_fqcln) = explode('::', $interface_method_ids[0]);
                        $interface_storage = $storage_provider->get($interface_fqcln);

                        if (isset($interface_storage->methods[$method_name])) {
                            $interface_method_storage = $interface_storage->methods[$method_name];

                            if (!$method_storage->throws
                                && $method_storage->inheritdoc
                                && $interface_method_storage->throws
                            ) {
                                $method_storage->throws = $interface_method_storage->throws;
                            }

                            if ($interface_method_storage->return_type
                                && $interface_method_storage->signature_return_type
                                && $interface_method_storage->return_type
                                    !== $interface_method_storage->signature_return_type
                            ) {
                                $method_storage->return_type = $interface_method_storage->return_type;
                                $method_storage->inherited_return_type = true;
                            }
                        }
                    }
                }
                $storage->overridden_method_ids[$method_name][$interface_method_ids[0]] = $interface_method_ids[0];
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

        $all_required_file_paths = $storage->required_file_paths;

        foreach ($storage->required_file_paths as $included_file_path => $_) {
            try {
                $included_file_storage = $this->file_storage_provider->get($included_file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateFileStorage($included_file_storage, $dependent_file_paths);

            $all_required_file_paths = $all_required_file_paths + $included_file_storage->required_file_paths;
        }

        foreach ($all_required_file_paths as $included_file_path => $_) {
            try {
                $included_file_storage = $this->file_storage_provider->get($included_file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $storage->declaring_function_ids = array_merge(
                $included_file_storage->declaring_function_ids,
                $storage->declaring_function_ids
            );

            $storage->declaring_constants = array_merge(
                $included_file_storage->declaring_constants,
                $storage->declaring_constants
            );
        }

        foreach ($storage->referenced_classlikes as $fq_class_name) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if (!$classlike_storage->location) {
                continue;
            }

            try {
                $included_file_storage = $this->file_storage_provider->get($classlike_storage->location->file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $storage->declaring_function_ids = array_merge(
                $included_file_storage->declaring_function_ids,
                $storage->declaring_function_ids
            );
        }

        $storage->required_file_paths = $all_required_file_paths;

        foreach ($all_required_file_paths as $required_file_path) {
            try {
                $required_file_storage = $this->file_storage_provider->get($required_file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $required_file_storage->required_by_file_paths += [$file_path_lc => $storage->file_path];
        }

        foreach ($storage->required_classes as $required_classlike) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($required_classlike);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if (!$classlike_storage->location) {
                continue;
            }

            try {
                $required_file_storage = $this->file_storage_provider->get($classlike_storage->location->file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $required_file_storage->required_by_file_paths += [$file_path_lc => $storage->file_path];
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

        if (isset($atomic_types['array']) && count($atomic_types) > 1 && !isset($atomic_types['null'])) {
            $iterator_name = null;
            $generic_params = null;
            $iterator_key = null;

            try {
                foreach ($atomic_types as $type_key => $type) {
                    if ($type instanceof Type\Atomic\TIterable
                        || ($type instanceof Type\Atomic\TNamedObject
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
                            ))
                    ) {
                        $iterator_name = $type->value;
                        $iterator_key = $type_key;
                    } elseif ($type instanceof Type\Atomic\TArray) {
                        $generic_params = $type->type_params;
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // ignore class-not-found issues
            }

            if ($iterator_name && $iterator_key && $generic_params) {
                if ($iterator_name === 'iterable') {
                    $generic_iterator = new Type\Atomic\TIterable($generic_params);
                } else {
                    if (strtolower($iterator_name) === 'generator') {
                        $generic_params[] = Type::getMixed();
                        $generic_params[] = Type::getMixed();
                    }
                    $generic_iterator = new Type\Atomic\TGenericObject($iterator_name, $generic_params);
                }

                $candidate->removeType('array');
                $candidate->removeType($iterator_key);
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
            ) {
                $aliased_method_names = array_merge(
                    $aliased_method_names,
                    array_keys($storage->trait_alias_map, $method_name, true)
                );
            }

            foreach ($aliased_method_names as $aliased_method_name) {
                if (isset($storage->appearing_method_ids[$aliased_method_name])) {
                    continue;
                }

                $implemented_method_id = $fq_class_name . '::' . $aliased_method_name;

                $storage->appearing_method_ids[$aliased_method_name] =
                    $parent_storage->is_trait ? $implemented_method_id : $appearing_method_id;

                $this_method_id = strtolower($fq_class_name . '::' . $method_name);

                if (isset($storage->methods[$aliased_method_name])) {
                    $storage->potential_declaring_method_ids[$aliased_method_name] = [$this_method_id => true];
                } else {
                    if (isset($parent_storage->potential_declaring_method_ids[$aliased_method_name])) {
                        $storage->potential_declaring_method_ids[$aliased_method_name]
                            = $parent_storage->potential_declaring_method_ids[$aliased_method_name];
                    }

                    $storage->potential_declaring_method_ids[$aliased_method_name][$this_method_id] = true;

                    $parent_method_id = strtolower($parent_storage->name . '::' . $method_name);
                    $storage->potential_declaring_method_ids[$aliased_method_name][$parent_method_id] = true;
                }
            }
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_method_ids as $method_name => $declaring_method_id) {
            if ($method_name !== '__construct') {
                if ($parent_storage->is_trait) {
                    $declaring_class = explode('::', $declaring_method_id)[0];
                    $declaring_class_storage = $this->classlike_storage_provider->get($declaring_class);

                    if (isset($declaring_class_storage->methods[$method_name])
                        && $declaring_class_storage->methods[$method_name]->abstract
                    ) {
                        $storage->overridden_method_ids[$method_name][$declaring_method_id] = $declaring_method_id;
                    }
                } else {
                    $storage->overridden_method_ids[$method_name][$declaring_method_id] = $declaring_method_id;
                }
            }

            $aliased_method_names = [$method_name];

            if ($parent_storage->is_trait
                && $storage->trait_alias_map
            ) {
                $aliased_method_names = array_merge(
                    $aliased_method_names,
                    array_keys($storage->trait_alias_map, $method_name, true)
                );
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
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $implemented_property_id = $storage->name . '::$' . $property_name;

            $storage->appearing_property_ids[$property_name] =
                $parent_storage->is_trait ? $implemented_property_id : $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_class) {
            if (isset($storage->declaring_property_ids[$property_name])) {
                continue;
            }

            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = $declaring_property_class;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
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
