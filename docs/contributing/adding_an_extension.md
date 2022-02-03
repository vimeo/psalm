# Adding an extension

Psalm tries to support most extensions with stubs so that projects requiring extensions can be analyzed without having
those extensions installed at runtime. When an extension isn't already supported by Psalm, if it is loaded at runtime
Psalm will use reflection to support it as best as possible, but it's always better to have stubs. In addition to
allowing analysis without having the extension installed, stubs can be more accurate. The gmp and decimal extensions
have a lot of functions that use `numeric-string` instead of `string`, and many extension like Ds, DOM, and others use
templates. Most extension stubs can be improved by using Psalm features that aren't possible from reflection alone.

## Using the stub generator

We try to make adding support for an extension as easy as possible by providing a simple way to generate stubs with
reflection, but keep in mind that reflection can miss a lot of useful information:

 - Which `@throws` tags should be added to a method
 - Psalm specific types like `numeric-string`, `int<0, max>`, or `non-empty-list`.
 - [Templates](../annotating_code/templated_annotations.md)

To use the extension stub generator, simply run `psalm --generate-extension-stub [extension-name]`. If you're working on
Psalm directly, you can direct the output like `psalm --generate-extension-stub [extension-name]
>stubs/extensions/reflection-generated/[extension-name-lowercase]-[extension-version].phpstub`, otherwise you can copy
the output into a GitHub issue or Gist to help us add support for the extension.

## Updating stubs

Generated stubs are kept for each version of an extension in `stubs/extensions/reflection-generated`.

When adding a new extension, simply copy the generated stub to `stubs/extensions/[extension-name-lowercase].phpstub` and
update as necessary with Psalm types. Alternatively, if the extension provides its own stub somewhere that includes
things like `@throws` tags or other features reflection isn't able to provide, use that as a base, but make sure
the stub isn't missing anything that shows up in the reflection generated stub.

When adding support for a different version of an extension, diff the reflection-generated
stubs to see what has changed between the versions and update `stubs/extensions/[extension-name-lowercase].phpstub` as
necessary (support for multiple versions is very limited until [#7512](https://github.com/vimeo/psalm/issues/7512) is
done).

## Adding the extension to the supported extensions lists

There are two places each extension needs added for it to work, the `"ExtensionType"` element in config.xsd and the
`$php_extensions` property in `Psalm\Config`. Once the extension is in both lists and the stubfile exists, the extension
stubs will be loaded automatically based on a project's composer.json and psalm.xml.
