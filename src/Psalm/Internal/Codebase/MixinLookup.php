<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;
use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeArgumentProjection;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_keys;
use function strtolower;

/**
 * A member a class lacks is looked up in its `@mixin`s, then in those of its traits, then in those of its parent
 * class, and so on, each class's in declaration order. The mixins of a mixin are looked up right after it. The first
 * mixin with the member wins, and a class reached a second time is skipped.
 *
 * @internal
 */
final class MixinLookup
{
    /**
     * The class, the traits it uses (each followed by the traits that one uses), then the same for each of its parent
     * classes, nearest first.
     *
     * @return list<ClassLikeStorage>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public static function getScopes(
        ClassLikeStorageProvider $classlike_storage_provider,
        ClassLikes $classlikes,
        ClassLikeStorage $storage,
    ): array {
        $scopes = [];
        $seen_traits = [];

        self::addScope($classlike_storage_provider, $classlikes, $storage, $scopes, $seen_traits);

        foreach ($storage->parent_classes as $parent_class_lc => $_) {
            try {
                $parent_storage = $classlike_storage_provider->get($parent_class_lc);
            } catch (InvalidArgumentException) {
                continue;
            }

            self::addScope($classlike_storage_provider, $classlikes, $parent_storage, $scopes, $seen_traits);
        }

        return $scopes;
    }

    /**
     * @param Closure(ClassLikeStorage): bool $has_member whether a mixin class has the member
     * @param bool $self_call whether the receiver is `$this`, whose templates stay unbound
     * @return array{type: TNamedObject, storage: ClassLikeStorage, declared: TNamedObject|TTemplateParam}|null
     *     the mixin with the member, bound for the receiver, its storage, and the mixin, as declared, of the class
     *     or of its traits or parent classes the member was reached through
     */
    public static function findMember(
        Codebase $codebase,
        ClassLikeStorage $storage,
        Atomic $receiver,
        Closure $has_member,
        bool $self_call = false,
    ): ?array {
        $visited = [];
        $seen_scopes = [];

        return self::find($codebase, $storage, $receiver, $has_member, $self_call, $visited, $seen_scopes);
    }

    /**
     * @param list<ClassLikeStorage> $scopes
     * @param array<lowercase-string, true> $seen_traits
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function addScope(
        ClassLikeStorageProvider $classlike_storage_provider,
        ClassLikes $classlikes,
        ClassLikeStorage $storage,
        array &$scopes,
        array &$seen_traits,
    ): void {
        $scopes[] = $storage;

        foreach ($storage->used_traits as $trait_lc => $_) {
            $trait_lc = strtolower($classlikes->getUnAliasedName($trait_lc));

            if (isset($seen_traits[$trait_lc])) {
                continue;
            }

            $seen_traits[$trait_lc] = true;

            try {
                $trait_storage = $classlike_storage_provider->get($trait_lc);
            } catch (InvalidArgumentException) {
                continue;
            }

            self::addScope($classlike_storage_provider, $classlikes, $trait_storage, $scopes, $seen_traits);
        }
    }

    /**
     * @param Closure(ClassLikeStorage): bool $has_member
     * @param array<lowercase-string, true> $visited
     * @param array<lowercase-string, true> $seen_scopes
     * @return array{type: TNamedObject, storage: ClassLikeStorage, declared: TNamedObject|TTemplateParam}|null
     */
    private static function find(
        Codebase $codebase,
        ClassLikeStorage $storage,
        Atomic $receiver,
        Closure $has_member,
        bool $self_call,
        array &$visited,
        array &$seen_scopes,
    ): ?array {
        // the members and mixins of the class and of its parent classes are the receiver's already
        $visited[strtolower($storage->name)] = true;

        foreach ($storage->parent_classes as $parent_class_lc => $_) {
            $visited[$parent_class_lc] = true;
        }

        $scopes = [];
        $self_storage = $storage;

        foreach (self::getScopes($codebase->classlike_storage_provider, $codebase->classlikes, $storage) as $scope) {
            // each class comes before its traits, whose `self` means that class
            if (!$scope->is_trait) {
                $self_storage = $scope;
            }

            $scope_name_lc = strtolower($scope->name);
            $scopes[] = [$scope, $self_storage, isset($seen_scopes[$scope_name_lc])];
            $seen_scopes[$scope_name_lc] = true;
        }

        foreach ($scopes as [$scope, $self_storage, $seen_scope]) {
            foreach ($scope->getMixins() as $mixin) {
                if ($mixin instanceof TNamedObject
                    // the named mixins of a class or trait shared with a class looked up before stay bound to that
                    // class, while a `@mixin T` is bound to the type the receiver gives T
                    && ($seen_scope
                        || isset($visited[strtolower($codebase->classlikes->getUnAliasedName($mixin->value))]))
                ) {
                    continue;
                }

                $bound_mixin = self::bind(
                    $codebase,
                    $mixin,
                    $scope,
                    $self_storage,
                    $storage,
                    $receiver,
                    $self_call,
                );

                if ($bound_mixin === null) {
                    continue;
                }

                $mixin_name_lc = strtolower($codebase->classlikes->getUnAliasedName($bound_mixin->value));

                if (isset($visited[$mixin_name_lc])) {
                    continue;
                }

                try {
                    $mixin_storage = $codebase->classlike_storage_provider->get($mixin_name_lc);
                } catch (InvalidArgumentException) {
                    continue;
                }

                if ($has_member($mixin_storage)) {
                    return ['type' => $bound_mixin, 'storage' => $mixin_storage, 'declared' => $mixin];
                }

                $found = self::find(
                    $codebase,
                    $mixin_storage,
                    $bound_mixin,
                    $has_member,
                    false,
                    $visited,
                    $seen_scopes,
                );

                if ($found !== null) {
                    return ['declared' => $mixin] + $found;
                }
            }
        }

        return null;
    }

    /**
     * The mixin with the templates of the class declaring it replaced by their types for the receiver, or null when
     * that is not a single class.
     *
     * @param ClassLikeStorage $self_storage the class declaring the mixin, or using the trait declaring it
     */
    private static function bind(
        Codebase $codebase,
        TNamedObject|TTemplateParam $mixin,
        ClassLikeStorage $declaring_storage,
        ClassLikeStorage $self_storage,
        ClassLikeStorage $storage,
        Atomic $receiver,
        bool $self_call,
    ): ?TNamedObject {
        if ($mixin instanceof TTemplateParam) {
            try {
                $declaring_storage = $codebase->classlike_storage_provider->get($mixin->defining_class);
            } catch (InvalidArgumentException) {
                return null;
            }

            $self_storage = $declaring_storage;
        } elseif ($declaring_storage->is_trait) {
            // the `self` of a trait's docblock is resolved to the trait when it is scanned
            $mixin = $mixin->replaceClassLike($declaring_storage->name, $self_storage->name);
        }

        $template_params = ClassTemplateParamCollector::collect(
            $codebase,
            $declaring_storage,
            $storage,
            null,
            $receiver,
            $self_call,
        );

        $mixin_template_result = null;

        if ($mixin instanceof TTemplateParam) {
            if ($template_params === null) {
                return null;
            }

            $bound_mixin = TemplateInferredTypeReplacer::replace(
                new Union([$mixin]),
                new TemplateResult([], $template_params),
                $codebase,
            );
        } else {
            $mixin_template_result = new TemplateResult([], $template_params ?? []);
            // a receiver, not what is read: its methods take arguments, so a covariant `*` stays a `*` too
            $mixin_template_result->star_receiver = true;
            ClassTemplateParamCollector::markStarBindings(
                $mixin_template_result,
                $codebase,
                $storage,
                $receiver,
                $template_params,
            );
            $bound_mixin = new Union([$mixin->replaceTemplateTypesWithArgTypes(
                $mixin_template_result,
                $codebase,
            )]);
        }

        $bound_mixin = self::expand($codebase, $bound_mixin, $self_storage, $storage, $receiver);
        $bound_mixin = $bound_mixin->isSingle() ? $bound_mixin->getSingleAtomic() : null;

        if ($mixin_template_result !== null
            && $mixin_template_result->star_bindings !== []
            && $mixin instanceof TGenericObject
            && $bound_mixin instanceof TGenericObject
            && $bound_mixin->hasStarProjection()
        ) {
            $bound_mixin = self::holdReadTypes(
                $codebase,
                $mixin,
                $bound_mixin,
                $mixin_template_result,
                $self_storage,
                $storage,
                $receiver,
            );
        }

        return $bound_mixin instanceof TNamedObject ? $bound_mixin : null;
    }

    private static function expand(
        Codebase $codebase,
        Union $type,
        ClassLikeStorage $self_storage,
        ClassLikeStorage $storage,
        Atomic $receiver,
    ): Union {
        return TypeExpander::expandUnion(
            $codebase,
            $type,
            $self_storage->name,
            $receiver instanceof TGenericObject ? $receiver : $storage->name,
            $self_storage->parent_class,
            true,
            false,
            $storage->final,
        );
    }

    /**
     * The bound mixin with each `*` holding what its slot reads from the receiver (`Box<T>` of a `Many<*>` with
     * `T of Post`: `Post`) where that is narrower than the bound of the template of the mixin. Each slot is read on
     * its own, with the star marks, so a star template inside it (`Box<Pair<T, int>>`) stays a `*` or `never`.
     * The result must not be expanded again: StarProjection::boundStars() would reset the held types to the bounds.
     */
    private static function holdReadTypes(
        Codebase $codebase,
        TGenericObject $mixin,
        TGenericObject $bound_mixin,
        TemplateResult $mixin_template_result,
        ClassLikeStorage $self_storage,
        ClassLikeStorage $storage,
        Atomic $receiver,
    ): TGenericObject {
        $read_result = clone $mixin_template_result;
        $read_result->star_receiver = false;
        $read_type_params = [];

        foreach ($mixin->type_params as $type_param) {
            $read_type_params[] = TemplateInferredTypeReplacer::replace($type_param, $read_result, $codebase);
        }

        $read_mixin = self::expand(
            $codebase,
            new Union([$mixin->setTypeParams($read_type_params)]),
            $self_storage,
            $storage,
            $receiver,
        );
        $read_mixin = $read_mixin->isSingle() ? $read_mixin->getSingleAtomic() : null;

        if (!$read_mixin instanceof TGenericObject) {
            return $bound_mixin;
        }

        $type_params = $bound_mixin->type_params;

        foreach (array_keys($bound_mixin->getProjections(), TypeArgumentProjection::Star, true) as $offset) {
            $read_type = $read_mixin->type_params[$offset] ?? null;

            if ($read_type !== null
                && UnionTypeComparator::isContainedBy($codebase, $read_type, $type_params[$offset])
            ) {
                $type_params[$offset] = $read_type;
            }
        }

        return $bound_mixin->setHeldTypes($type_params);
    }
}
