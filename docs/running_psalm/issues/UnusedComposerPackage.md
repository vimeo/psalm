# UnusedComposerPackage

Emitted when `composer.json` contains a package that is not referenced in the analyzed project.  

To fix, remove the package from the `require` section of `composer.json`.  

Peer dependencies (dependencies which are only used by another dependency, aren't explicitly required by that dependency's composer.json, and aren't used in the current project) may be excluded from unused composer package detection by using the [ignoreUnusedComposerPackages](https://psalm.dev/docs/running_psalm/configuration/#ignoreunusedcomposerpackages) config.