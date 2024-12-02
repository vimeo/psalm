<?php

namespace Psalm\Type;

use LogicException;
use Psalm\Issue\TaintedCallable;
use Psalm\Issue\TaintedCookie;
use Psalm\Issue\TaintedCustom;
use Psalm\Issue\TaintedEval;
use Psalm\Issue\TaintedFile;
use Psalm\Issue\TaintedHeader;
use Psalm\Issue\TaintedHtml;
use Psalm\Issue\TaintedInclude;
use Psalm\Issue\TaintedInput;
use Psalm\Issue\TaintedLdap;
use Psalm\Issue\TaintedSSRF;
use Psalm\Issue\TaintedShell;
use Psalm\Issue\TaintedSql;
use Psalm\Issue\TaintedSystemSecret;
use Psalm\Issue\TaintedTextWithQuotes;
use Psalm\Issue\TaintedUnserialize;
use Psalm\Issue\TaintedUserSecret;
use ReflectionClass;
use RuntimeException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_splice;
use function array_unique;
use function array_values;
use function class_exists;
use function in_array;
use function is_int;
use function is_subclass_of;
use function sprintf;
use function stripos;

use const ARRAY_FILTER_USE_KEY;

final class TaintKindRegistry
{
    private const CLASS_NAME_PREFIX = 'Tainted';

    /**
     * @var non-empty-array<non-empty-string, class-string<TaintedInput>>
     */
    private array $taint_kinds = [
        TaintKind::INPUT_CALLABLE => TaintedCallable::class,
        TaintKind::INPUT_COOKIE => TaintedCookie::class,
        TaintKind::INPUT_EVAL => TaintedEval::class,
        TaintKind::INPUT_FILE => TaintedFile::class,
        TaintKind::INPUT_HAS_QUOTES => TaintedTextWithQuotes::class,
        TaintKind::INPUT_HEADER => TaintedHeader::class,
        TaintKind::INPUT_HTML => TaintedHtml::class,
        TaintKind::INPUT_INCLUDE => TaintedInclude::class,
        TaintKind::INPUT_LDAP => TaintedLdap::class,
        TaintKind::INPUT_SHELL => TaintedShell::class,
        TaintKind::INPUT_SQL => TaintedSql::class,
        TaintKind::INPUT_SSRF => TaintedSSRF::class,
        TaintKind::INPUT_UNSERIALIZE => TaintedUnserialize::class,
        TaintKind::SYSTEM_SECRET => TaintedSystemSecret::class,
        TaintKind::USER_SECRET => TaintedUserSecret::class,
    ];
    /**
     * @var non-empty-array<non-empty-string, list<non-empty-string>>
     */
    private array $taint_groups = [
        TaintKindGroup::GROUP_INPUT => [
            TaintKind::INPUT_CALLABLE,
            TaintKind::INPUT_COOKIE,
            TaintKind::INPUT_EVAL,
            TaintKind::INPUT_FILE,
            TaintKind::INPUT_HAS_QUOTES,
            TaintKind::INPUT_HEADER,
            TaintKind::INPUT_HTML,
            TaintKind::INPUT_INCLUDE,
            TaintKind::INPUT_LDAP,
            TaintKind::INPUT_SHELL,
            TaintKind::INPUT_SQL,
            TaintKind::INPUT_SSRF,
            TaintKind::INPUT_UNSERIALIZE,
        ],
    ];

    /**
     * @var array<string, array{group: string, kinds: array<string, class-string<TaintedInput>>}>
     */
    private array $group_proxies = [];

    /**
     * @var class-string<TaintedInput>
     */
    private string $default_kind = TaintedCustom::class;

    /**
     * @internal
     */
    public function __construct()
    {
    }

    /**
     * @param array<string, class-string<TaintedInput>> $kind_to_class_map
     */
    public function defineKinds(array $kind_to_class_map, string $group = ''): void
    {
        foreach ($kind_to_class_map as $kind => $class_name) {
            if ($kind === '') {
                throw new LogicException('Taint kind cannot be an empty string');
            }
            if ($this->hasKind($kind)) {
                throw new RuntimeException('Taint kind ' . $kind . ' is already defined');
            }
            $this->assertClassName($class_name);
            $this->assertDisjunctiveNames($kind);
            $this->taint_kinds[$kind] = $class_name;
        }
        if ($group === '') {
            return;
        }
        if ($this->hasGroup($group)) {
            $this->extendGroup($group, ...array_keys($kind_to_class_map));
        } else {
            $this->defineGroup($group, ...array_keys($kind_to_class_map));
        }
    }

    /**
     * @param non-empty-string $group
     */
    public function defineGroup(string $group, string ...$kinds): void
    {
        if ($this->hasGroup($group)) {
            throw new LogicException('Taint group ' . $group . ' is already defined');
        }
        $kinds = array_filter($kinds);
        $this->assertKinds(...$kinds);
        $this->assertDisjunctiveNames($group);
        $this->taint_groups[$group] = array_values(array_unique($kinds));
    }

    /**
     * @param non-empty-string $group
     */
    public function extendGroup(string $group, string ...$kinds): void
    {
        if (!$this->hasGroup($group)) {
            throw new LogicException('Taint group ' . $group . ' is not defined');
        }
        $kinds = array_filter($kinds);
        $this->assertKinds(...$kinds);
        $this->taint_groups[$group] = array_values(array_unique(array_merge($this->taint_groups[$group], $kinds)));
    }

    /**
     * @param array<string, class-string<TaintedInput>> $refined_kind_to_class_map
     */
    public function defineGroupProxy(string $proxy, string $group, array $refined_kind_to_class_map): void
    {
        if (isset($this->group_proxies[$proxy])) {
            throw new LogicException('Group proxy ' . $proxy . ' is already defined');
        }
        foreach ($refined_kind_to_class_map as $kind => $class_name) {
            if ($kind === '') {
                throw new LogicException('Taint kind cannot be an empty string');
            }
            if (!$this->hasKind($kind)) {
                throw new RuntimeException('Taint kind ' . $kind . ' is not defined');
            }
            $this->assertClassName($class_name);
        }
        $this->assertDisjunctiveNames($proxy);
        $this->group_proxies[$proxy] = [
            'group' => $group,
            'kinds' => $refined_kind_to_class_map,
        ];
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @param class-string<TaintedInput> $class_name
     */
    public function setDefaultKind(string $class_name): void
    {
        $this->assertClassName($class_name);
        $this->default_kind = $class_name;
    }

    /**
     * @return class-string<TaintedInput>|null
     */
    public function getKind(string $kind): ?string
    {
        return $this->taint_kinds[$kind] ?? null;
    }

    /**
     * @return class-string<TaintedInput>
     */
    public function getDefaultKind(): string
    {
        return $this->default_kind;
    }

    public function getKindProxy(string $kind, string $proxy): ?string
    {
        return $this->group_proxies[$proxy]['kinds'][$kind] ?? null;
    }

    /**
     * @param non-empty-string $kind
     */
    public function hasKind(string $kind): bool
    {
        return isset($this->taint_kinds[$kind]);
    }

    /**
     * Resolves one level of items (e.g. 'html', 'sql', ...) for a named group (e.g. 'index')
     * (does not resolve recursively).
     *
     * @return list<string>
     */
    public function getGroupKinds(string $group): array
    {
        return $this->taint_groups[$group] ?? [];
    }

    /**
     * @param non-empty-string $group
     * @return array<array-key, string>
     */
    public function resolveGroupKinds(string $group, string ...$names): array
    {
        if (!$this->hasGroup($group)) {
            return $names;
        }
        $groupIndex = array_search($group, $names, true);
        if ($groupIndex !== false && is_int($groupIndex)) {
            array_splice(
                $names,
                $groupIndex,
                1,
                $this->taint_groups[$group],
            );
        }
        return $names;
    }

    /**
     * @return array<array-key, string>
     */
    public function resolveAllGroupKinds(string ...$names): array
    {
        foreach (array_keys($this->taint_groups) as $group) {
            $names = $this->resolveGroupKinds($group, ...$names);
        }
        return $names;
    }

    /**
     * @param non-empty-string $group
     */
    public function hasGroup(string $group): bool
    {
        return isset($this->taint_groups[$group]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function resolveProxyKindMap(string ...$kinds): array
    {
        if ($this->group_proxies === []) {
            return [];
        }
        $group_proxies = array_filter(
            $this->group_proxies,
            static fn(string $proxy) => in_array($proxy, $kinds, true),
            ARRAY_FILTER_USE_KEY,
        );
        return array_map(
            fn(array $item) => $this->getGroupKinds($item['group']),
            $group_proxies,
        );
    }

    /**
     * @param non-empty-string ...$kinds
     */
    private function assertKinds(string ...$kinds): void
    {
        foreach ($kinds as $kind) {
            if (!$this->hasKind($kind)) {
                throw new RuntimeException('Taint kind ' . $kind . ' is not defined');
            }
        }
    }

    private function assertDisjunctiveNames(string $name): void
    {
        if (isset($this->taint_kinds[$name])) {
            throw new LogicException('All taint names need to be disjunctive, but found kind ' . $name);
        }
        if (isset($this->taint_groups[$name])) {
            throw new LogicException('All taint names need to be disjunctive, but found group ' . $name);
        }
        if (isset($this->group_proxies[$name])) {
            throw new LogicException('All taint names need to be disjunctive, but found proxy ' . $name);
        }
    }

    private function assertClassName(string $class_name): void
    {
        if (!class_exists($class_name)) {
            throw new LogicException(sprintf(
                'Taint class %s does not exist',
                $class_name,
            ));
        }
        if (!is_subclass_of($class_name, TaintedInput::class)) {
            throw new LogicException(sprintf(
                'Taint class %s must be a subclass of %s',
                $class_name,
                TaintedInput::class,
            ));
        }
        if (stripos($this->getShortClassName($class_name), self::CLASS_NAME_PREFIX) !== 0) {
            throw new LogicException(sprintf(
                'Taint class name %s must start with "%s"',
                $class_name,
                self::CLASS_NAME_PREFIX,
            ));
        }
    }

    /**
     * Resolves `Vendor\Package\ClassName` to just `ClassName`
     *
     * @param class-string $class_name
     */
    private function getShortClassName(string $class_name): string
    {
        return (new ReflectionClass($class_name))->getShortName();
    }
}
