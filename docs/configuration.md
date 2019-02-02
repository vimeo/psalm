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

#### `totallyTyped`
```xml
<psalm
  totallyTyped="[bool]"
/>
```
Enabling this will make Psalm very strict, such that it needs to be able to evaluate the type of every single statement, and emitting a bevy of `Mixed*` issues if the types cannot be determined. Defaults to `false`.

#### `useDocblockTypes`
```xml
<psalm
  useDocblockTypes="[bool]"
>
```
Whether or not to use types as defined in docblocks. Defaults to `true`.

#### `useDocblockPropertyTypes`
```xml
<psalm
  useDocblockPropertyTypes="[bool]"
>
```
If not using all docblock types, you can still use docblock property types. Defaults to `false` (though only relevant if `useDocblockTypes` is `false`.

#### `usePhpDocMethodsWithoutMagicCall`
```xml
<psalm
  usePhpDocMethodsWithoutMagicCall="[bool]"
>
```
The PHPDoc `@method` annotation normally only applies to classes with a `__call` method. Setting this to `true` allows you to use the `@method` annotation to override inherited method return types. Defaults to `false`.

#### `strictBinaryOperands`
```xml
<psalm
  strictBinaryOperands="[bool]"
>
```
If true we force strict typing on numerical and string operations (see https://github.com/vimeo/psalm/issues/24). Defaults to `false`.

#### `requireVoidReturnType`
```xml
<psalm
  requireVoidReturnType="[bool]"
>
```
If `false`, Psalm will not complain when a function with no return types is missing an explicit `@return` annotation. Defaults to `true`.

#### `useAssertForType`
```xml
<psalm
  useAssertForType="[bool]"
>
```
Some like to use [`assert`](http://php.net/manual/en/function.assert.php) for type checks. If `true`, Psalm will process assertions inside `assert` calls. Defaults to `true`.

#### `rememberPropertyAssignmentsAfterCall`
```xml
<psalm
  rememberPropertyAssignmentsAfterCall="[bool]"
>
```
Setting this to `false` means that any function calls will cause Psalm to forget anything it knew about object properties within the scope of the function it's currently analysing. This duplicates functionality that Hack has. Defaults to `true`.

#### `allowPhpStormGenerics`
```xml
<psalm
  allowPhpStormGenerics="[bool]"
>
```
Allows you to specify whether or not to use the typed iterator docblock format supported by PHP Storm e.g. `ArrayIterator|string[]`, which Psalm transforms to `ArrayIterator<string>`. Defaults to `false`.

#### `allowCoercionFromStringToClassConst`
```xml
<psalm
  allowCoercionFromStringToClassConst="[bool]"
>
```
When `true`, strings can be coerced to [`class-string`](supported_annotations.md#class-constants), with Psalm emitting a `TypeCoercion` issue. If disabled, that issue changes to a more serious one. Defaults to `true`.

#### `allowStringToStandInForClass`
```xml
<psalm
  allowStringToStandInForClass="[bool]"
>
```
When `true`, strings can be used as classes, meaning `$some_string::someMethod()` is allowed. If `false`, only class constant strings (of the form `Foo\Bar::class`) can stand in for classes, otherwise an `InvalidStringClass` issue is emitted. Defaults to `false`.

#### `memoizeMethodCallResults`
```xml
<psalm
  memoizeMethodCallResults="[bool]"
>
```
When `true`, the results of method calls without arguments passed arguments are remembered between repeated calls of that method on a given object. Defaults to `false`.

#### `hoistConstants`
```xml
<psalm
  hoistConstants="[bool]"
>
```
When `true`, constants defined in a function in a file are assumed to be available when requiring that file, and not just when calling that function. Defaults to `false` (i.e. constants defined in functions will *only* be available for use when that function is called)

#### `addParamDefaultToDocblockType`
```xml
<psalm
  addParamDefaultToDocblockType="[bool]"
>
```
Occasionally a param default will not match up with the docblock type. By default, Psalm emits an issue. Setting this flag to `true` causes it to expand the param type to include the param default. Defaults to `false`.

#### `checkForThrowsDocblock`
```xml
<psalm
  checkForThrowsDocblock="[bool]"
>
```
When `true`, Psalm will check that the developer has supplied `@throws` docblocks for every exception thrown in a given function or method. Defaults to `false`.

#### `ignoreInternalFunctionFalseReturn`
```xml
<psalm
  ignoreInternalFunctionFalseReturn="[bool]"
>
```
When `true`, Psalm ignores possibly-false issues stemming from return values of internal functions (like `preg_split`) that may return false, but do so rarely). Defaults to `true`.

#### `ignoreInternalFunctionNullReturn`
```xml
<psalm
  ignoreInternalFunctionNullReturn="[bool]"
>
```
When `true`, Psalm ignores possibly-null issues stemming from return values of internal array functions (like `current`) that may return null, but do so rarely. Defaults to `true`.

### Running Psalm

#### `autoloader`
```xml
<psalm
  autoloader="[string]"
>
```
if your application registers one or more custom autoloaders, and/or declares universal constants/functions, this autoloader script will be executed by Psalm before scanning starts. Psalm always registers composer's autoloader by default.

#### `throwExceptionOnError`
```xml
<psalm
  throwExceptionOnError="[bool]"
>
```
Useful in testing, things makes Psalm throw a regular-old exception when it encounters an error. Defaults to `false`.

#### `hideExternalErrors`
```xml
<psalm
  hideExternalErrors="[bool]"
>
```
whether or not to show issues in files that are used by your project files, but which are not included in `<projectFiles>`. Defaults to `false`.

#### `cacheDirectory`
```xml
<psalm
  cacheDirectory="[string]"
>
```
The directory used to store Psalm's cache data - if you specify one (and it does not already exist), its parent directory must already exist, otherwise Psalm will throw an error.

#### `allowFileIncludes`
```xml
<psalm
  allowFileIncludes="[bool]"
>
```
Whether or not to allow `require`/`include` calls in your PHP. Defaults to `true`.

#### `serializer`
```xml
<psalm
  serializer="['igbinary'|'default']"
>
```
Allows you to hard-code a serializer for Psalm to use when caching data. By default, Psalm uses `ext-igbinary` *if* the version is greater or equal to 2.0.5, otherwise it defaults to PHP's built-in serializer.


## Project settings

#### `<projectFiles>`
Contains a list of all the directories that Psalm should inspect. You can also specify a set of files and folders to ignore with the `<ignoreFiles>` directive, e.g.
```xml
<projectFiles>
  <directory name="src" />
  <ignoreFiles>
    <directory name="src/Stubs" />
  </ignoreFiles>
</projectFiles>
```

#### `<fileExtensions>` 
Optional.  A list of extensions to search over. See [Checking non-PHP files](checking_non_php_files.md) to understand how to extend this.

#### `<plugins>`
Optional.  A list of `<plugin filename="path_to_plugin.php" />` entries. See the [Plugins](plugins.md) section for more information.

#### `<issueHandlers>`
Optional.  If you don't want Psalm to complain about every single issue it finds, the issueHandler tag allows you to configure that. [Dealing with code issues](dealing_with_code_issues.md) tells you more.

#### `<mockClasses>`
Optional. Do you use mock classes in your tests? If you want Psalm to ignore them when checking files, include a fully-qualified path to the class with `<class name="Your\Namespace\ClassName" />`

#### `<stubs>`
Optional. If your codebase uses classes and functions that are not visible to Psalm via reflection (e.g. if there are internal packages that your codebase relies on that are not available on the machine running Psalm), you can use stub files. Used by PhpStorm (a popular IDE) and others, stubs provide a description of classes and functions without the implementations. You can find a list of stubs for common classes [here](https://github.com/JetBrains/phpstorm-stubs). List out each file with `<file name="path/to/file.php" />`.
