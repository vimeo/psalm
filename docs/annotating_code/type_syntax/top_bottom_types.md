
# Top types, bottom types

## `mixed`

This is the _top type_ in PHP's type system, and represents a lack of type information. Psalm warns about `mixed` types when the `reportMixedIssues` flag is turned on, or when you're on level 1.

## `never`

It can be aliased to `no-return` or `never-return` in docblocks. Note: it replaced the old `empty` type that used to exist in Psalm

This is the _bottom type_ in PHP's type system. It's used to describe a type that has no possible value. It can happen in multiple cases:
- the actual `never` type from PHP 8.1 (can be used in docblocks for older versions). This type can be used as a return type for functions that will never return, either because they always throw exceptions or always exit()
- an union type that have been stripped for all its possible types. (For example, if a variable is `string|int` and we perform a is_bool() check in a condition, the type of the variable in the condition will be `never` as the condition will never be entered)
- it can represent a placeholder for types yet to come — a good example is the type of the empty array `[]`, which Psalm types as `array<never, never>`, the content of the array is void so it can accept any content
- it can also happen in the same context as the line above for templates that have yet to be defined
