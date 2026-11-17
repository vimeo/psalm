# Helping Psalm understand your library

If you maintain a library, the people using it get better results from Psalm the more Psalm knows about your types. There are three ways to tell it, from cheapest to most work. Use the first one that covers your case.

1. [Annotations in your source](#1-annotations-in-your-source). No Psalm specific files, nothing for users to enable.
2. [Stub files](#2-stub-files) for what the source cannot express.
3. [A plugin shipped in your package](#3-a-plugin-in-your-package) that registers those stubs (and, if needed, hooks) for users who enable it.

## 1. Annotations in your source

Psalm reads docblocks of your classes and functions wherever it finds them, including in `vendor/`. Users get the types without any configuration.

### Templates

```php
<?php
namespace Acme;

/** @template T of object */
final class Container
{
    /**
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        return new $id();
    }
}
```

```php
<?php
/** @param \Acme\Container<Foo> $container */
function f(\Acme\Container $container): void {
    $foo = $container->get(Foo::class); // Foo
}
```

See [Templated annotations](templated_annotations.md).

### Assertions

```php
<?php
namespace Acme;

final class Guard
{
    /** @psalm-assert non-empty-string $value */
    public static function notEmpty(string $value): void
    {
    }
}
```

```php
<?php
function f(string $s): void {
    \Acme\Guard::notEmpty($s);
    $s; // non-empty-string
}
```

See [Adding assertions](adding_assertions.md) for `@psalm-assert-if-true`, `@psalm-assert-if-false` and `@psalm-this-out`.

### Conditional return types

```php
<?php
namespace Acme;

final class Reader
{
    /**
     * @param 'int'|'string' $type
     * @return ($type is 'int' ? int : string)
     */
    public static function read(string $type): int|string
    {
        return $type === 'int' ? 1 : 'x';
    }
}
```

```php
<?php
$a = \Acme\Reader::read('int'); // int
$b = \Acme\Reader::read('string'); // string
```

See [Conditional types](type_syntax/conditional_types.md).

### Magic methods, magic properties and mixins

```php
<?php
namespace Acme;

/**
 * @method string name()
 * @psalm-property-read int $id
 */
class Entity
{
    public function __call(string $name, array $args): mixed { /* ... */ }
    public function __get(string $name): mixed { /* ... */ }
}

final class Logger
{
    public function info(string $message): void {}
}

/** @mixin Logger */
final class Facade {}
```

```php
<?php
function f(\Acme\Entity $e, \Acme\Facade $f): void {
    $e->name(); // string
    $e->id; // int
    $f->info('x'); // no issue, forwarded to Logger
    $f->nope(); // UndefinedMethod
}
```

See [`@method`, `@psalm-property` and `@mixin`](supported_annotations.md).

### `@phpstan-*` tags

Psalm reads these tags as the `@psalm-*` equivalents, so a library that already annotates for PHPStan needs no second set:

`@phpstan-param`, `@phpstan-return`, `@phpstan-var`, `@phpstan-template`, `@phpstan-template-covariant`, `@phpstan-extends`, `@phpstan-implements`, `@phpstan-assert`, `@phpstan-assert-if-true`, `@phpstan-assert-if-false`, `@phpstan-param-out`, `@phpstan-pure`, `@phpstan-type`, `@phpstan-import-type`.

[Supported annotations](supported_annotations.md) names a few more (`@phpstan-this-out`, `@phpstan-self-out`, `@phpstan-param-closure-this`, `@phpstan-require-extends`, `@phpstan-require-implements`).

Psalm does not read `@phpstan-method` and `@phpstan-property`, so use `@method` or `@psalm-method` and `@property` or `@psalm-property`:

```php
<?php
/**
 * @phpstan-method string name()
 */
final class Entity
{
    public function __call(string $name, array $args): mixed { /* ... */ }
}

function f(Entity $e): void {
    $e->name(); // UndefinedMagicMethod
}
```

## 2. Stub files

When the source cannot say what you mean (for example a signature that has to stay untyped for BC), describe it in a stub file shipped in the package. A stub declares the classes and functions it refines; it is not analyzed, and Psalm uses its types for them.

```php
<?php
// stubs/Config.phpstub
namespace Acme\Config;

/** @psalm-stub-override */
final class Config
{
    /**
     * @psalm-stub-override
     * @return non-empty-string
     */
    public static function get(string $key) {}
}
```

`@psalm-stub-override` makes Psalm stop with an error when the class or method no longer exists in the source, so a stub that went stale after a rename is noticed. See [`@psalm-stub-override`](supported_annotations.md#psalm-stub-override) and [Stub files](../running_psalm/plugins/authoring_plugins.md#stub-files).

Psalm does not find stubs in `vendor/` by itself. Users either list them in their `psalm.xml` ([`<stubs>`](../running_psalm/configuration.md#stubs)), or the next section registers them for them.

## 3. A plugin in your package

A library can ship its own plugin. No separate package is needed and `type` stays `library`: Psalm looks for `extra.psalm.pluginClass` in the `composer.json` of every installed package.

```json
{
    "name": "acme/config",
    "type": "library",
    "autoload": { "psr-4": { "Acme\\Config\\": "src/" } },
    "extra": {
        "psalm": {
            "pluginClass": "Acme\\Config\\PsalmPlugin"
        }
    }
}
```

The entry point class implements `Psalm\Plugin\PluginEntryPointInterface`. This one registers the stub file from above:

```php
<?php
namespace Acme\Config;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

final class PsalmPlugin implements PluginEntryPointInterface
{
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        $registration->addStubFile(__DIR__ . '/../stubs/Config.phpstub');
    }
}
```

The same `__invoke()` can also call `registerHooksFromClass()` for [plugin hooks](../running_psalm/plugins/authoring_plugins.md#psalm-api). The class is only loaded when Psalm runs, so the library does not have to require Psalm.

Users turn it on once:

```
$ vendor/bin/psalm-plugin enable acme/config
```

which adds the plugin to their `psalm.xml`. Without that step Psalm ignores the plugin, and the issue it fixes reappears:

```php
<?php
function takesNonEmpty(string $s): void {}

takesNonEmpty(\Acme\Config\Config::get('APP_KEY')); // MixedArgument without the plugin, no issue with it
```

Write the `enable` step in your README. Psalm enables a plugin without it only for the packages it knows about (see [`loadLibraryPlugins`](../running_psalm/configuration.md#loadlibraryplugins)).
