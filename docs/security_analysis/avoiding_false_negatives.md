# Avoiding false-negatives

## Unescaping statements

Post-processing previously escaped/encoded statements can cause insecure scenarios.
`@psalm-taint-unescape <taint-type>` allows to declare those components insecure explicitly.

```php
<?php

/**
 * @psalm-taint-unescape html
 */
function decode(string $str): string
{
    return str_replace(
        ['&lt;', '&gt;', '&quot;', '&apos;'],
        ['<', '>', '"', '"'],
        $str
    );
}

$safe = htmlspecialchars($_GET['text']);
echo decode($safe);
```
