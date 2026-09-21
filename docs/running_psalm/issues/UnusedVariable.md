# UnusedVariable

Emitted when `--find-dead-code` is turned on and Psalm cannot find any references to a variable, once instantiated

```php
<?php

function foo() : void {
    $a = 5;
    $b = 4;
    echo $b;
}
```

Can be suppressed by prefixing the variable name with an underscore:

```php
<?php

$_a = 22;
```


If the variable contains an object with an impure destructor, the issue is not emitted, to allow for patterns used for example in timers or mutexes:

```php
<?php

class Timer {
    private readonly float $start;
    public function __construct(private readonly string $op) {
        $this->start = microtime(true);
    }
    /**
     * Must be explicitly marked as @psalm-impure.
     * 
     * @psalm-impure
     */
    public function __destruct() {
        $took = microtime(true) - $this->start;
        // Send to influx, or similar
        echo "{$this->op} took $took seconds!\n";
    }
}

function foo() : void {
    // OK!
    $timer = new Timer('file_get_contents');

    echo file_get_contents('https://httpbin.io/delay/3');
}

foo();

$var = [new Timer('bad')];

// UnusedVariable: The issue will still be emitted even if the destructor is impure for variables used as foreach variables.
foreach ($var as $timer) {

}
```

