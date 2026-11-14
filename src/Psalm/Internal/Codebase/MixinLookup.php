<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;
use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

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
     * @return array{TNamedObject, ClassLikeStorage}|null the mixin with the member, bound for the receiver
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
     * @return array{TNamedObject, ClassLikeStorage}|null
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

        // a class or trait shared with a class looked up before keeps its mixins bound to that class
        $scopes = [];

        foreach (self::getScopes($codebase->classlike_storage_provider, $codebase->classlikes, $storage) as $scope) {
            $scope_name_lc = strtolower($scope->name);

            if (!isset($seen_scopes[$scope_name_lc])) {
                $seen_scopes[$scope_name_lc] = true;
                $scopes[] = $scope;
            }
        }

        foreach ($scopes as $scope) {
            foreach ($scope->getMixins() as $mixin) {
                if ($mixin instanceof TNamedObject
                    && isset($visited[strtolower($codebase->classlikes->getUnAliasedName($mixin->value))])
                ) {
                    continue;
                }

                $bound_mixin = self::bind($codebase, $mixin, $scope, $storage, $receiver, $self_call);

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
                    return [$bound_mixin, $mixin_storage];
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
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * The mixin with the templates of the class declaring it replaced by their types for the receiver, or null when
     * that is not a single class.
     */
    private static function bind(
        Codebase $codebase,
        TNamedObject|TTemplateParam $mixin,
        ClassLikeStorage $declaring_storage,
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
        }

        $template_params = ClassTemplateParamCollector::collect(
            $codebase,
            $declaring_storage,
            $storage,
            null,
            $receiver,
            $self_call,
        );

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
            $bound_mixin = new Union([$mixin->replaceTemplateTypesWithArgTypes(
                new TemplateResult([], $template_params ?? []),
                $codebase,
            )]);
        }

        $bound_mixin = TypeExpander::expandUnion(
            $codebase,
            $bound_mixin,
            $declaring_storage->name,
            $receiver instanceof TGenericObject ? $receiver : $storage->name,
            $declaring_storage->parent_class,
            true,
            false,
            $storage->final,
        );

        $bound_mixin = $bound_mixin->isSingle() ? $bound_mixin->getSingleAtomic() : null;

        return $bound_mixin instanceof TNamedObject ? $bound_mixin : null;
    }
}
