# ForbiddenCode

Emitted when Psalm encounters a var_dump, exec or similar expression that may make your code more vulnerable

```php
<?php

var_dump("bah");
```

This functions list can be extended by configuring `forbiddenFunctions` or `forbiddenConstants` in `psalm.xml`

```xml
<?xml version="1.0"?>
<psalm>
    <!-- other configs -->

    <forbiddenFunctions>
        <function name="dd"/>
        <function name="var_dump"/>
    </forbiddenFunctions>
    
    <forbiddenConstants>
        <constant name="FILTER_VALIDATE_URL" />
    </forbiddenConstants>
</psalm>
```
