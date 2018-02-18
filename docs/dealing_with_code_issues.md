# Dealing with code issues

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

## Suppressing issues

There are two ways to suppress an issue – via the Psalm config or via a function docblock.

### Config suppression

You can use the `<issueHandlers>` tag in the config file to influence how issues are treated.

```xml
<issueHandlers>
  <MissingPropertyType errorLevel="suppress" />

  <InvalidReturnType>
    <errorLevel type="suppress">
      <directory name="some_bad_directory" /> <!-- all InvalidReturnType issues in this directory are suppressed -->
      <file name="some_bad_file.php" />  <!-- all InvalidReturnType issues in this file are suppressed -->
    </errorLevel>
  </InvalidReturnType>
</issueHandlers>
```

### Docblock suppression

You can also use `@psalm-suppress IssueName` on a function's docblock to suppress Psalm issues e.g.

```php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  return $a;
}
```

You can also suppress issues at the line level e.g.

```php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  /**
   * @psalm-suppress InvalidReturnStatement
   */
  return $a;
}
```
