# Configuration

Psalm uses an XML config file. A barebones example looks like this:

```xml
<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
    </projectFiles>
</psalm>
```

## Optional `<psalm />` attributes

### Coding style

- `totallyTyped=[bool]`<br />
  enabling this will make Psalm very strict, such that it needs to be able to evaluate the type of every single statement, and emitting a bevy of `Mixed*` issues if the types cannot be determined. Defaults to `false`.
- `useDocblockTypes=[bool]`<br />
  whether or not to use types as defined in docblocks. Defaults to `true`.
- `useDocblockPropertyTypes=[bool]`<br />
  if not using all docblock types, you can still use docblock property types. Defaults to `false` (though only relevant if `useDocblockTypes` is `false`.
- `strictBinaryOperands=[bool]`<br />
  if true we force strict typing on numerical and string operations (see https://github.com/vimeo/psalm/issues/24). Defaults to `false`.
- `requireVoidReturnType=[bool]`<br />
  if `false`, Psalm will not complain when a function with no return types is missing an explicit `@return` annotation. Defaults to `true`.
- `useAssertForType=[bool]`<br />
  Some like to use [`assert`](http://php.net/manual/en/function.assert.php) for type checks. If `true`, Psalm will process assertions inside `assert` calls. Defaults to `false`.
- `rememberPropertyAssignmentsAfterCall=[bool]`<br />
  Setting this to `false` means that any function calls will cause Psalm to forget anything it knew about object properties within the scope of the function it's currently analysing. This duplicates functionality that Hack has. Defaults to `true`.
- `allowPhpStormGenerics=[bool]`<br />
  Allows you to specify whether or not to use the typed iterator docblock format supported by PHP Storm e.g. `ArrayIterator|string[]`, which Psalm transforms to `ArrayIterator<string>`. Defaults to `false`.

### Running Psalm

- `autoloader=[string]`<br />
  if your application registers one or more custom autoloaders, and/or declares universal constants/functions, this autoloader script will be executed by Psalm before scanning starts. Psalm always registers composer's autoloader by default.
- `throwExceptionOnError=[bool]`<br />
  useful in testing, things makes Psalm throw a regular-old exception when it encounters an error. Defaults to `false`.
- `hideExternalErrors=[bool]`<br />
  whether or not to show issues in files that are used by your project files, but which are not included in `<projectFiles>`. Defaults to `false`.
- `cacheDirectory=[string]`<br />
  the directory used to store Psalm's cache data - if you specify one (and it does not already exist), its parent directory must already exist, otherwise Psalm will throw an error.
- `allowFileIncludes=[bool]`<br />
  whether or not to allow `require`/`include` calls in your PHP. Defaults to `true`.
- `serializer=["igbinary"|"default"]`<br />
  Allows you to hard-code a serializer for Psalm to use when caching data. By default Psalm uses `ext-igbinary` *if* the version is greater or equal to 2.0.5, otherwise it defaults to PHP's built-in serializer.


## Project settings

- `<projectFiles>`<br />
  Contains a list of all the directories that Psalm should inspect
- `<fileExtensions>` (optional)<br />
  A list of extensions to search over. See [Checking non-PHP files](checking_non_php_files.md) to understand how to extend this.
- `<plugins>` (optional)<br />
  A list of `<plugin filename="path_to_plugin.php" />` entries. See the [Plugins](plugins.md) section for more information.
- `<issueHandlers>` (optional)<br />
  If you don't want Psalm to complain about every single issue it finds, the issueHandler tag allows you to configure that. [Dealing with code issues](dealing_with_code_issues.md) tells you more.
- `<mockClasses>` (optional)<br />
  Do you use mock classes in your tests? If you want Psalm to ignore them when checking files, include a fully-qualified path to the class with `<class name="Your\Namespace\ClassName" />`
- `<stubs>` (optional)<br />
  If you codebase uses classes and functions that are not visible to Psalm via reflection (e.g. if there are internal packages that your codebase relies on that are not available on the machine running Psalm), you can use stub files. Used by PhpStorm (a popular IDE) and others, stubs provide a description of classes and functions without the implementations. You can find a list of stubs for common classes [here](https://github.com/JetBrains/phpstorm-stubs). List out each file with `<file name="path/to/file.php" />`.
