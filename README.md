<h1><img src="PsalmLogo.png" height="64" alt="logo" /></h1>

Psalm is a static analysis tool for finding errors in PHP scripts.


- [Installation](#installation)
- [Configuration](#configuration)
- [Running Psalm](#running-psalm)
- [Dealing with code issues](#dealing-with-code-issues)
- [Typing in Psalm](#typing-in-psalm)
- [Plugins](#plugins)
- [Checking non-PHP files](#checking-non-php-files)

## Installation

To install Psalm, use [Composer](https://getcomposer.org/).

@todo add detailed composer instructions with public github.com address

## Configuration

Psalm uses an XML config file. A recommended default Psalm config is located [here](examples/psalm.default.xml).

### Options

- `stopOnFirstError`<br />
  whether or not to stop when the first error is encountered
- `useDocblockTypes`<br />
  whether or not to use types as defined in docblocks
- `autoloader` (optional)
  if you script that registers a custom autoloader and/or universal constants/functions, register them here

### Parameters

- `<inspectFiles>`<br />
  Contains a list of all the directories that Psalm should inspect
- `<fileExtensions>` (optional)<br />
  A list of extensions to search over. See [Checking non-PHP files](#checking-non-php-files) to understand how to extend this.
- `<plugins>` (optional)<br />
  A list of `<plugin filename="path_to_plugin.php" />` entries. See the [Plugins](#plugins) section for more information.
- `<issueHandler>` (optional)<br />
  If you don't want Psalm to complain about every single issue it finds, the issueHandler tag allows you to configure that. [Dealing with code issues](#dealing-with-code-issues) tells you more.
- `<includeHandler>` (optional)<br />
  If there are files that your scripts include that you don't want Psalm to traverse, include them here with `<file name="path_to_file.php" />`.
- `<mockClasses>` (optional)<br />
  Do you use mock classes in your tests? If you want Psalm to ignore them when checking files, include a fully-qualified path to the class with `<class name="Your\Namespace\ClassName" />`

## Running Psalm

Once you've set up your config file, you can run Psalm from your project's root directory with
```bash
./vendor/bin/psalm
```

### Command-line options

- `--help`<br />
  Display the list of help options
- `--debug`<br />
  With this flag, Psalm will list the files it's scanning, and provide a summary of memory usage
- `--config`<br />
  Path to a configuration file, if not ./psalm.xml
- `-f [FILE_PATH]`<br />
  Path to run checks against (if ommitted, Psalm will scan all files in the project referenced by `<inspectFiles>`
- `--monochrome`<br />
  Disables colored output
- `--show-info=[BOOLEAN]`
  Show non-error parser findings.
- `--diff`<br />
  Only check files that have changed (and their dependents) since the last successful run

## Dealing with code issues

Code issues in Psalm fall into three categories:
<dl>
  <dt>error</dt>
  <dd>this will cause Psalm to print a message, and to ultimately terminate with a non-zero exist status</dd>
  <dt>info</dt>
  <dd>this will cause Psalm to print a message</dd>
  <dt>suppress</dt>
  <dd>this will cause Psalm to ignore the code issue entirely</dd>
</dl>

The third category, `suppress`, is the one you will probably be most interested in, especially when introducing Psalm to a large codebase.

### Suppressing issues

There are two ways to suppress an issue – via the Psalm config or via a function docblock.

#### Config suppression

You can use the `<issueHandler>` tag in the config file to influence how issues are treated.

```xml
<issueHandler>
  <MissingPropertyType errorLevel="suppress" />

  <InvalidReturnType>
    <excludeFiles>
      <directory name="some_bad_directory" /> <!-- all InvalidReturnType issues in this directory are suppressed -->
      <file name="some_bad_file.php" />  <!-- all InvalidReturnType issues in this file are suppressed -->
    </excludeFiles>
  </InvalidReturnType>
</issueHandler>
```

#### Docblock suppression

You can also use `@psalm-suppress IssueName` on a function's docblock to suppress Psalm issues e.g.

```php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  return $a;
}
```

## Typing in Psalm

Psalm is able to interpret all PHPDoc type annotations, and use them to further understand the codebase.

### Union Types

@todo describe how Union types work

### Property types vs Assignment typehints

You can use the `/** @var Type */` docblock to annotate both property declarations and to help Psalm understand variable assignment.

#### Property types

You can specify a particular type for an class property in Psalm by using the `@var` declaration:

```php
/** @var string|null */
public $foo;
```

When checking `$this->foo = $some_variable;`, Psalm will check to see whether `$some_variable` is either `string` or `null` and, if neither, emit an issue.

If you leave off the property type docblock, Psalm will emit a `MissingPropertyType` issue.

#### Assignment typehints

Consider the following code:

```php
$a = null;

foreach ([1, 2, 3] as $i) {
  if ($a) {
    return $a;
  }
  else {
    $a = $i;
  }
}
```

Because Psalm scans a file progressively, it cannot tell that `return $a` produces an integer. Instead it returns knows only that `$a` is not `empty`. We can fix this by adding a type hint docblock:

```php
/** @var int|null */
$a = null;

foreach ([1, 2, 3] as $i) {
  if ($a) {
    return $a;
  }
  else {
    $a = $i;
  }
}
```

This tells Psalm that `int` is a possible type for `$a`, and allows it to infer that `return $a;` produces an integer.

Unlike property types, however, assignment typehints are not binding – they can be overridden by a new assignment without Psalm emitting an issue e.g.

```php
/** @var string|null */
$a = foo();
$a = 6; // $a is now typed as an int
```

You can also use typehints on specific variables e.g.

```php
/** @var string $a */
echo strpos($a, 'hello');
```

This tells Psalm to assume that `$a` is a string (though it will still throw an error if `$a` is undefined).

### Typing arrays

In PHP, the `array` type is commonly used to represent three different data structures:
 - a [List](https://en.wikipedia.org/wiki/List_(abstract_data_type))

   ```php
   $a = [1, 2, 3, 4, 5];
   ```
 - an [Associative array](https://en.wikipedia.org/wiki/Associative_array)

   ```php
   $a = [0 => 'hello', 5 => 'goodbye'];
   $a = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
   ```
 - makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language))

   ```php
   $a = ['name' => 'Psalm', 'type' => 'tool'];
   ```

PHP treats all these arrays the same, essentially (though there are some optimisations under the hood for the first case).

PHPDoc [allows you to specify](https://phpdoc.org/docs/latest/references/phpdoc/types.html#arrays) the  type of values the array holds with the anootation:
```php
/** @return TValue[] */
```

where `TValue` is a union type, but it does not allow you to specify the type of keys.

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) to denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

#### Makeshift Structs

Ideally (in the author's opinion), all data would either be encoded as lists, associative arrays, or as well-defined objects. However, PHP arrays are often used as makeshift structs.

Hack (by Facebook) supports this usage by way of the [Shape datastructure](https://docs.hhvm.com/hack/shapes/introduction), but there is no agreed-upon documentation format for such arrays in regular PHP-land.

Psalm solves this by adding another way annotate array types, by using an object-like syntax when describing them.

So, for instance,
```php
$a = ['name' => 'Psalm', 'type' => 'tool'];
```
is assigned the type `array{ name: string, type: string}`.

#### Backwards compatibility

Psalm fully supports PHPDoc's array typing syntax, such that any array typed with `TValue[]` will be typed in Psalm as `array<mixed, TValue>`. That also extends to generic type definitions with only one param e.g. `array<TValue>`, which is equivalent to `array<mixed, TValue>`.

## Plugins

@todo add this

## Checking non-PHP files

Psalm supports the ability to check various PHPish files by extending the `FileChecker` class. For example, if you have a template where the variables are set elsewhere, Psalm can scrape those variables and check the template with those variables pre-populated.

An example TemplateChecker is provided [here](examples/TemplateChecker.php).

To ensure your custom `FileChecker` is used, you must update the Psalm `fileExtensions` config in psalm.xml:
```xml
<fileExtensions>
    <extension name=".php" />
    <extension name=".phpt" filetypeHandler="path/to/TemplateChecker.php" />
</fileExtensions>
```
