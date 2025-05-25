# UnusedExtension

Emitted when `composer.json` contains an extension that is not referenced in the analyzed project.  

To fix, remove that extension from the `require` section of `composer.json`.  

Peer dependencies (extensions which are only used by another dependency, aren't explicitly required by that dependency's composer.json, and aren't used in the current project) may be excluded from unused composer package detection by using the [ignoreUnusedExtensions](https://psalm.dev/docs/running_psalm/configuration/#ignoreunusedextensions) config.