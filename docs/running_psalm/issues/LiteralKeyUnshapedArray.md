# LiteralKeyUnshapedArray

Emitted when a literal key is used on an unshaped array.  

All known array shapes must be annotated using [array shape syntax](/docs/annotating_code/type_syntax/array_types/#object-like-arrays), to allow additional Psalm paradox checks on array keys and types.  

When working with arrays whose shape is not known yet (i.e. user input), a **separate validation function** must be used with a function-level suppress.  

By splitting validation and business logic, Psalm can make more and better typechecks of existing usages of the business logic.


```php
<?php

class DTO {
    /** @var array{0: string, 1: string} */
    public array $key1;
    /** @var positive-int */
    public int $key2;

    /**
     * @param array{key1: array{0: string, 1: string}, key2: positive-int} $input
     */
    public function __construct(array $input) {
        // DO NOT validate here, validate in DTO::validate!
        $this->key1 = $input['key1'];
        $this->key2 = $input['key2'];
    }

    /**
     * @param array $input
     * @return array{key1: array{0: string, 1: string}, key2: positive-int}
     * 
     * @psalm-suppress LiteralKeyUnshapedArray This is required in validation logic.
     * 
     * @throws AssertionError
     */
    public static function validate(array $input): array {
        if (!isset($input['key1'])
            || !is_array($input['key1'])
            || !isset($input['key1'][0])
            || !isset($input['key1'][1])
            || !is_string($input['key1'][0])
            || !is_string($input['key1'][1])
        ) {
            throw new AssertionError('Key1 is invalid!');
        }
        if (!isset($input['key2']) || !is_int($input['key2']) || $input['key2'] <= 0) {
            throw new AssertionError('Key2 is invalid!');
        }
        return $input;
    }
}

// OK!
$dto = new DTO(DTO::validate($_GET));

// In some old place in the codebase, a DTO was constructed incorrectly...
// This is now a Psalm error!

// ERROR: InvalidArgument - Argument 1 of DTO::__construct expects array{key1: array{0: string, 1: string}, key2: positive-int}, array{key1: "d", key2: -1} provided
$dto2 = new DTO(['key1' => 'd', 'key2' => -1]);
```