# Avoiding false-positives

When you run Psalm's taint analysis for the first time you may see a bunch of false-positives.

Nobody likes false-positives!

There are a number of ways you can prevent them:

## Escaping tainted input

Some operations remove taints from data – for example, wrapping `$_GET['name']` in an `htmlentities` call prevents cross-site-scripting attacks in that `$_GET` call.

Psalm allows you to remove taints via a `@psalm-taint-escape <taint-type>` annotation:

```php
<?php

function echoVar(string $str) : void {
    /**
     * @psalm-taint-escape html
     */
    $str = str_replace(['<', '>'], '', $str);
    echo $str;
}

echoVar($_GET["text"]);
```

## Types

A value can only carry the taints its type can hold: a number or a boolean can't hold HTML, SQL or a path, a string can't be a NoSQL query document, and a literal string (a value the code wrote, or one Psalm knows is among such values) can't hold anything the client sent. An array holds what its keys and values can: `list<int>` holds no HTML, while the string keys of `array<string, int>` can hold anything. A union holds what any of its types can hold, and `null` holds nothing.

Psalm removes the taints a value can't carry where it is cast, passed to a function, returned, or assigned to a variable or a property, using the type it infers for the value, and where PHP guarantees a type:

- casts: `(int) $_GET['id']` is only left with the taints a number can carry;
- the native type of a parameter, inside the function;
- the native return type of a function or method, for what it returns, taint sources included: a method annotated `@psalm-taint-source input` with a `string` return type never returns a NoSQL query.

```php
<?php

/** @psalm-taint-source input */
function getParam(string $name): string {
    return (string) ($_GET[$name] ?? '');
}

function getId(string $name): int {
    return (int) getParam($name);
}

$collection->find(['name' => getParam('name')]); // a string can't inject query operators
echo '<b>' . getId('id') . '</b>'; // an int can't carry HTML

$direction = getParam('direction');
if ($direction === 'asc' || $direction === 'desc') {
    echo $direction; // 'asc'|'desc' is a literal
}
```

A function that validates a value against data the code trusts can tell Psalm about it with `@psalm-assert-if-true literal-string`: once validated, the value can't hold anything the client chose.

```php
<?php

/** @psalm-assert-if-true literal-string $city */
function isKnownCity(string $city): bool {
    return in_array($city, getCityKeysFromDatabase(), true);
}

$city = getParam('city');
if (isKnownCity($city)) {
    $organization->city = $city; // stored without any taint
}
```

## Conditionally escaping tainted input

A slightly modified version of the previous example is using a condition to determine whether the return value
is considered secure. Only in case function argument `$escape` is true, the corresponding annotation
`@psalm-taint-escape` is applied for taint type `html` .

```php
<?php
/**
 * @param string $str
 * @param bool $escape
 * @psalm-taint-escape ($escape is true ? 'html' : null)
 */
function processVar(string $str, bool $escape = true) : string {
    if ($escape) {
      $str = str_replace(['<', '>'], '', $str);
    }
    return $str;
}

echo processVar($_GET['text'], false); // detects tainted HTML
echo processVar($_GET['text'], true); // considered secure
```

## Sanitizing HTML user input

Whenever possible, applications should be designed to accept & store user input as discrete text fields, rather than blocks of HTML.  This allows user input to be fully escaped via `htmlspecialchars` or `htmlentities`.  In cases where HTML user input is required (e.g. rich text editors like [TinyMCE](https://www.tiny.cloud/)), a library designed specifically to filter out risky HTML is highly recommended.  For example, [HTML Purifier](http://htmlpurifier.org/docs) could be used as follows:

```php
<?php

/**
 * @psalm-taint-escape html
 * @psalm-taint-escape has_quotes
 */
function sanitizeHTML($html){
    $purifier = new HTMLPurifier();
    return $purifier->purify($html);
}
```

## Specializing taints in functions

For functions, methods and classes you can use the `@psalm-taint-specialize` annotation.

```php
<?php

function takesInput(string $s) : string {
    error_log("Got input");
    return $s;
}

echo htmlentities(takesInput($_GET["name"]));
echo takesInput("hello"); // Psalm detects tainted HTML here
```

Adding a `@psalm-taint-specialize` annotation solves the problem, by telling Psalm that each invocation of the function should be treated separately.

```php
<?php

/**
 * @psalm-taint-specialize
 */
function takesInput(string $s) : string {
    error_log("Got input");
    return $s;
}

echo htmlentities(takesInput($_GET["name"]));
echo takesInput("hello"); // No error
```

A specialized function or method will still track tainted input:

```php
<?php

/**
 * @psalm-taint-specialize
 */
function takesInput(string $s) : string {
    return $s;
}

echo takesInput($_GET["name"]); // Psalm detects tainted input
echo takesInput("hello"); // No error
```

Here we’re telling Psalm that a function’s taintedness is wholly dependent on the input to the function.

If you're familiar with [immutability in Psalm](https://psalm.dev/articles/immutability-and-beyond) then this general idea should be familiar, since a pure function is one where the output is wholly dependent on its input. Unsurprisingly, all functions marked `@psalm-pure` _also_ specialize the taintedness of their output based on input:

```php
<?php

/**
 * @psalm-pure
 */
function takesInput(string $s) : string {
    return $s;
}

echo htmlentities(takesInput($_GET["name"]));
echo takesInput("hello"); // No error
```

Since each invocation is treated separately, a sink the input reaches through a specialized function – in its body or in a function it calls – is reported for every call that passes tainted input to it:

```php
<?php

/**
 * @psalm-taint-specialize
 */
function run(string $command) : void {
    exec($command); // Reported twice: once for each call below
}

run($_GET["first"]);
run($_GET["second"]);
```

Psalm also infers the purity of functions without a purity annotation, so an unannotated function is specialized too when it turns out to be pure, as would be the first `takesInput` above without the `error_log` call. Since a method could be overridden by an impure one, this only applies to methods that cannot be overridden: `private` or `final` methods, and methods of `final` classes.

## Specializing taints in classes

Just as taints can be specialized in function calls, tainted properties can also be specialized to a given class.

```php
<?php

class User {
    public string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user) {
    echo $user->name; // Error, detected tainted input
}

$user1 = new User("Keith");
$user2 = new User($_GET["name"]);

echoUserName($user1);
```

Adding `@psalm-taint-specialize` to the class fixes the issue.

```php
<?php

/**
 * @psalm-taint-specialize
 */
class User {
    public string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user) {
    echo $user->name; // No error
}

$user1 = new User("Keith");
$user2 = new User($_GET["name"]);

echoUserName($user1);
```

And, because it’s form of purity enforcement, `@psalm-immutable` can also be used:

```php
<?php

/**
 * @psalm-immutable
 */
class User {
    public string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user) {
    echo $user->name; // No error
}

$user1 = new User("Keith");
$user2 = new User($_GET["name"]);

echoUserName($user1);
```

## Avoiding files in taint paths

You can also tell Psalm that you’re not interested in any taint paths that flow through certain files or directories by specifying them in your Psalm config:

```xml
    <taintAnalysis>
        <ignoreFiles>
            <directory name="tests"/>
        </ignoreFiles>
    </taintAnalysis>
```
