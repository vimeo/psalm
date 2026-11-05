# Common questions

Before opening an issue, check whether one of these is the explanation.

## Psalm.dev and my machine report different things

[psalm.dev](https://psalm.dev) runs a recent Psalm with its own settings. Locally the result depends on your [`phpVersion`](configuration.md#phpversion), the [error level](error_levels.md), `findUnusedCode` and the other settings of `psalm.xml`, and on what Psalm can load from `vendor/`.

## `UndefinedClass` for a class that exists

Psalm only knows classes that are in the analysed files, in a stub, or that Composer's autoloader can find. A class of a PHP extension also needs the extension in `composer.json` (`ext-…`) or in [`enableExtensions`](configuration.md#enableextensions); see [UndefinedClass](issues/UndefinedClass.md). Files that define such classes but are not autoloaded can be listed in [`extraFiles`](configuration.md#extrafiles).

## No `Mixed*` issues show up

`Mixed*` issues are only info at the default [error level](error_levels.md) 2 (printed with `--show-info=true`); level 1 reports them as errors.

## `PropertyNotSetInConstructor` or `InaccessibleProperty` for a property I do set

Psalm requires a property to be initialized in the constructor (or in private or final methods the constructor calls), and a `readonly` property can only be written there: [PropertyNotSetInConstructor](issues/PropertyNotSetInConstructor.md), [InaccessibleProperty](issues/InaccessibleProperty.md).

## `RedundantConditionGivenDocblockType` for a check that is needed

The type comes from a docblock, which Psalm trusts, so a check against it is redundant for Psalm even if the value comes from outside (user input, a library). Make the type wider where the value enters, or see [RedundantConditionGivenDocblockType](issues/RedundantConditionGivenDocblockType.md).

## Reporting a bug

Please include a link to [psalm.dev](https://psalm.dev) that reproduces it, or the smallest code and `psalm.xml` that do.
