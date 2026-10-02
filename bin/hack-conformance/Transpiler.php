<?php

/**
 * Transpiles a Hack conformance fixture into PHP with Psalm docblocks, so that the
 * verdict HHVM gives a fixture can be asserted of Psalm on the same program.
 *
 * The input is not Hack source but its full-fidelity parse tree, as HHVM's own
 * parser prints it (`hh_parse --full-fidelity-json-parse-tree`, see lib.php), so
 * the transpiler never has to guess what a token means.
 *
 * It covers the subset of Hack the fixtures are written in, not Hack as a whole:
 * classes, functions, generics, function types, contexts and context constants,
 * lambdas, Hack arrays and shapes, `inout`, `readonly` values, `is` and function
 * pointers. Every other kind of node is reported as an UnexpectedValueException
 * naming it, rather than translated wrongly; extend the transpiler when a new
 * fixture needs more.
 *
 * Types become a native PHP type plus, when the native type loses information, a
 * docblock type (`vec<T>` is `array` + `list<T>`). Contexts become capabilities:
 * a function-like without a context has Hack's `[defaults]`, `@psalm-impure`,
 * and a context list is `@psalm-capabilities`, always including `read-props`,
 * since every Hack context may read properties (Psalm's `pure` may not):
 *
 *   []                -> read-props
 *   [write_props]     -> read-props|write-this-props|write-props
 *   [read_globals]    -> read-props|read-globals
 *   [globals]         -> read-props|read-globals|write-globals
 *   [defaults]        -> impure
 *   [ctx $f]          -> the `Closure[_]` of $f (Psalm's wildcard purity)
 *   [this::C]         -> @psalm-purity-from-template C
 *   [Cls::C]          -> the capability alias C (`@psalm-type`/`@psalm-import-type`)
 *
 * A lambda without a context has the context of the function-like around it, as
 * in Hack. An `inout` parameter is a by-reference parameter, which Psalm charges
 * `write-refs` for writing (writing one is no effect in Hack). Abstract context
 * constants are class purity templates (`as` is the lower bound, `super` the
 * upper one), and a subclass binding one is an `@extends Parent[...]`.
 *
 * CLI only (lives under bin/, which Psalm does not analyse); see transpile.php.
 */

declare(strict_types=1);

namespace Psalm\HackConformance;

use UnexpectedValueException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function ltrim;
use function max;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_repeat;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * Throws UnexpectedValueException for Hack it cannot transpile.
 */
final class Transpiler
{
    private const CAPABILITY_ORDER = [
        'read-props',
        'write-this-props',
        'write-props',
        'read-globals',
        'write-globals',
        'write-refs',
        'io',
    ];

    private const HACK_CAPABILITIES = [
        'write_props' => ['write-this-props', 'write-props'],
        'read_globals' => ['read-globals'],
        'globals' => ['read-globals', 'write-globals'],
    ];

    /** Hack types an `is` test cannot turn into `instanceof` */
    private const NON_CLASS_TYPES = [
        'int', 'string', 'bool', 'float', 'num', 'arraykey', 'mixed', 'dynamic', 'nothing',
        'vec', 'dict', 'keyset', 'shape', 'tuple', 'nonnull', 'null', '_',
    ];

    /** Statement and expression nodes that are the same in PHP: copied token by token */
    private const VERBATIM_NODES = [
        'compound_statement', 'expression_statement', 'return_statement', 'echo_statement',
        'if_statement', 'else_clause', 'while_statement', 'throw_statement',
        'binary_expression', 'postfix_unary_expression',
        'conditional_expression', 'parenthesized_expression', 'variable', 'qualified_name',
        'subscript_expression', 'member_selection_expression', 'safe_member_selection_expression',
        'scope_resolution_expression', 'cast_expression', 'element_initializer', 'field_initializer',
        'simple_type_specifier',
    ];

    /** Literal tokens that mean the same in PHP */
    private const LITERAL_TOKENS = [
        'decimal_literal', 'hexadecimal_literal', 'binary_literal', 'octal_literal', 'floating_literal',
        'single_quoted_string_literal', 'double_quoted_string_literal', 'boolean_literal',
        'true', 'false', 'null',
    ];

    private const TRIVIA = ['whitespace', 'end_of_line', 'single_line_comment', 'delimited_comment'];

    /** @var array<string, array<string, mixed>> classes by lowercase name */
    private array $classes = [];

    /** @var array<string, mixed>|null the class being emitted */
    private ?array $class = null;

    /** @var array<string, true> template names in scope */
    private array $templates = [];

    /** @var array<string, string> `@psalm-import-type` lines the class being emitted needs */
    private array $imports = [];

    /** Whether a `[_]` context may be emitted: only in the type of a `ctx $param` parameter */
    private bool $wildcardAllowed = false;

    /** @var list<list<string>> the context tags of the function-likes being emitted, innermost last */
    private array $contextStack = [];

    /** @var array<string, true> variables defined so far in the function-like being emitted */
    private array $seen = [];

    /** The nesting of the declaration being emitted (1 for a method), whose indentation its body loses */
    private int $depth = 0;

    /** Whether the output is at the start of a line, where whitespace is indentation */
    private bool $lineStart = false;

    /** The kind of the node whose child is being emitted */
    private string $parent = '';

    /** Whether the next token is emitted without its leading trivia */
    private bool $skipLeading = false;

    private function __construct(private readonly string $file)
    {
    }

    /**
     * @param array<string, mixed> $tree the `script` node `hh_parse --full-fidelity-json-parse-tree` prints
     * @return string PHP code, starting with `<?php`
     */
    public static function transpile(array $tree, string $file = 'fixture'): string
    {
        return (new self($file))->run($tree);
    }

    /** @param array<string, mixed> $tree */
    private function run(array $tree): string
    {
        if (($tree['kind'] ?? null) !== 'script') {
            throw new UnexpectedValueException("{$this->file}: not a Hack parse tree");
        }

        $decls = [];
        foreach (self::items($tree['script_declarations']) as $node) {
            $decls[] = match ($node['kind']) {
                'function_declaration' => $this->parseFunction(
                    $node['function_attribute_spec'],
                    $node['function_declaration_header'],
                    $node['function_body'],
                    false,
                ),
                'classish_declaration' => $this->parseClass($node),
                'end_of_file' => null,
                default => $this->fail("unsupported top-level `{$node['kind']}`", $node),
            };
        }
        $decls = array_values(array_filter($decls));

        foreach ($decls as $decl) {
            if ($decl['kind'] === 'class') {
                $this->classes[strtolower($decl['name'])] = $decl;
            }
        }

        $out = [];
        foreach ($decls as $decl) {
            $out[] = $decl['kind'] === 'class' ? $this->emitClass($decl) : $this->emitFunction($decl, 0);
        }

        return "<?php\n" . implode("\n\n", $out);
    }

    // ------------------------------------------------------------ parse tree

    /**
     * @param array<string, mixed>|null $node
     */
    private function fail(string $message, ?array $node = null): never
    {
        $token = $node === null ? null : self::firstToken($node);
        $where = $token === null ? '' : ":{$token['line_number']}";
        $near = $token === null ? '' : ' near `' . explode("\n", $token['text'])[0] . '`';
        throw new UnexpectedValueException("{$this->file}$where: $message$near");
    }

    /**
     * The first token of a node, with its trivia.
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>|null
     */
    private static function firstToken(array $node): ?array
    {
        if (($node['kind'] ?? null) === 'token') {
            return $node['token'];
        }
        foreach ($node as $key => $child) {
            if ($key !== 'kind' && is_array($child)) {
                $token = self::firstToken($child);
                if ($token !== null) {
                    return $token;
                }
            }
        }
        return null;
    }

    /**
     * The elements of a list node (none for `missing`, the node itself for a
     * lone node), without their separators.
     *
     * @param array<string, mixed> $node
     * @return list<array<string, mixed>>
     */
    private static function items(array $node): array
    {
        if ($node['kind'] === 'missing') {
            return [];
        }
        if ($node['kind'] !== 'list') {
            return [$node];
        }
        return array_map(
            static fn(array $item): array => $item['kind'] === 'list_item' ? $item['list_item'] : $item,
            $node['elements'],
        );
    }

    /** @param array<string, mixed> $node */
    private static function isMissing(array $node): bool
    {
        return $node['kind'] === 'missing';
    }

    /**
     * The source text of a node without trivia (names, qualified names).
     *
     * @param array<string, mixed> $node
     */
    private static function text(array $node): string
    {
        if ($node['kind'] === 'token') {
            return $node['token']['text'];
        }
        $text = '';
        foreach ($node as $key => $child) {
            if ($key !== 'kind' && is_array($child)) {
                $text .= self::text(isset($child['kind']) ? $child : ['kind' => 'list', 'elements' => $child]);
            }
        }
        return $text;
    }

    /**
     * Fails on a node that must be absent (an unsupported feature).
     *
     * @param array<string, mixed> $node
     */
    private function reject(array $node, string $what): void
    {
        if (!self::isMissing($node)) {
            $this->fail("unsupported $what", $node);
        }
    }

    // --------------------------------------------------------------- parsing

    /**
     * @param array<string, mixed> $node an old_attribute_specification, or missing
     * @return list<string>
     */
    private function parseAttributes(array $node): array
    {
        if (self::isMissing($node)) {
            return [];
        }
        if ($node['kind'] !== 'old_attribute_specification') {
            $this->fail("unsupported attribute syntax `{$node['kind']}`", $node);
        }
        $attributes = [];
        foreach (self::items($node['old_attribute_specification_attributes']) as $attribute) {
            if ($attribute['kind'] !== 'constructor_call') {
                $this->fail("unsupported attribute `{$attribute['kind']}`", $attribute);
            }
            $this->reject($attribute['constructor_call_left_paren'], 'attribute arguments');
            $attributes[] = self::text($attribute['constructor_call_type']);
        }
        return $attributes;
    }

    /**
     * @param array<string, mixed> $node a list of modifier tokens, or missing
     * @param list<string> $allowed
     * @return list<string>
     */
    private function parseModifiers(array $node, array $allowed): array
    {
        $modifiers = [];
        foreach (self::items($node) as $modifier) {
            $text = strtolower(self::text($modifier));
            if (!in_array($text, $allowed, true)) {
                $this->fail("unsupported modifier `$text`", $modifier);
            }
            $modifiers[] = $text;
        }
        return $modifiers;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $header a function_declaration_header
     * @param array<string, mixed> $body a compound_statement, or missing
     * @return array<string, mixed>
     */
    private function parseFunction(array $attributes, array $header, array $body, bool $method): array
    {
        $this->reject($header['function_where_clause'], 'where clause');
        $modifiers = $this->parseModifiers(
            $header['function_modifiers'],
            $method ? ['abstract', 'final', 'public', 'protected', 'private', 'static'] : [],
        );

        return [
            'kind' => 'function',
            'attributes' => $this->parseAttributes($attributes),
            'modifiers' => $modifiers,
            'name' => self::text($header['function_name']),
            'templates' => $this->parseTypeParameters($header['function_type_parameter_list']),
            'params' => $this->parseParameters($header['function_parameter_list'], false),
            'context' => $this->parseContextList($header['function_contexts']),
            // a readonly return (`: readonly T`) is about the value, which PHP has no notion of
            'return' => self::isMissing($header['function_type']) ? null : $this->parseType($header['function_type']),
            'body' => self::isMissing($body) ? null : $body,
        ];
    }

    /**
     * @param array<string, mixed> $node a classish_declaration
     * @return array<string, mixed>
     */
    private function parseClass(array $node): array
    {
        $this->reject($node['classish_attribute'], 'class attributes');
        $this->reject($node['classish_xhp'], 'XHP class');
        $this->reject($node['classish_where_clause'], 'where clause');
        $modifiers = $this->parseModifiers($node['classish_modifiers'], ['abstract', 'final']);
        if (in_array('abstract', $modifiers, true) && in_array('final', $modifiers, true)) {
            $this->fail('unsupported `abstract final` class', $node);
        }
        $classKind = strtolower(self::text($node['classish_keyword']));
        if ($classKind !== 'class' && $classKind !== 'interface') {
            $this->fail("unsupported `$classKind`", $node['classish_keyword']);
        }

        $members = [];
        foreach (self::items($node['classish_body']['classish_body_elements']) as $member) {
            $members[] = $this->parseMember($member);
        }

        return [
            'kind' => 'class',
            'classKind' => $classKind,
            'modifiers' => $modifiers,
            'name' => self::text($node['classish_name']),
            'templates' => $this->parseTypeParameters($node['classish_type_parameters']),
            'extends' => array_map($this->parseType(...), self::items($node['classish_extends_list'])),
            'implements' => array_map($this->parseType(...), self::items($node['classish_implements_list'])),
            'members' => $members,
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function parseMember(array $node): array
    {
        switch ($node['kind']) {
            case 'methodish_declaration':
                return $this->parseFunction(
                    $node['methodish_attribute'],
                    $node['methodish_function_decl_header'],
                    $node['methodish_function_body'],
                    true,
                );

            case 'trait_use':
                return ['kind' => 'use', 'names' => array_map(self::text(...), self::items($node['trait_use_names']))];

            case 'context_const_declaration':
                return $this->parseContextConstant($node);

            case 'const_declaration':
                $this->parseModifiers($node['const_modifiers'], []);
                $declarators = self::items($node['const_declarators']);
                if (count($declarators) !== 1) {
                    $this->fail('unsupported constant declaration of several constants', $node);
                }
                $initializer = $declarators[0]['constant_declarator_initializer'];
                if (self::isMissing($initializer)) {
                    $this->fail('unsupported constant without a value', $node);
                }
                // `const int X = 1;`: the type is optional in Hack, and dropped
                return [
                    'kind' => 'const',
                    'name' => self::text($declarators[0]['constant_declarator_name']),
                    'value' => $initializer['simple_initializer_value'],
                ];

            case 'property_declaration':
                $this->reject($node['property_attribute_spec'], 'property attributes');
                $declarators = self::items($node['property_declarators']);
                if (count($declarators) !== 1) {
                    $this->fail('unsupported property declaration of several properties', $node);
                }
                $initializer = $declarators[0]['property_initializer'];
                return [
                    'kind' => 'property',
                    // Hack's `readonly` is not PHP's: it is rejected, not translated
                    'modifiers' => $this->parseModifiers(
                        $node['property_modifiers'],
                        ['public', 'protected', 'private', 'static'],
                    ),
                    'type' => self::isMissing($node['property_type']) ? null : $this->parseType($node['property_type']),
                    'name' => self::text($declarators[0]['property_name']),
                    'default' => self::isMissing($initializer) ? null : $initializer['simple_initializer_value'],
                ];
        }

        $this->fail("unsupported class member `{$node['kind']}`", $node);
    }

    /**
     * @param array<string, mixed> $node a context_const_declaration
     * @return array<string, mixed>
     */
    private function parseContextConstant(array $node): array
    {
        $name = self::text($node['context_const_name']);
        $this->reject($node['context_const_type_parameters'], 'context constant type parameters');
        $abstract = $this->parseModifiers($node['context_const_modifiers'], ['abstract']) !== [];

        $as = null;
        $super = null;
        foreach (self::items($node['context_const_constraint']) as $constraint) {
            $keyword = strtolower(self::text($constraint['ctx_constraint_keyword']));
            $list = $this->parseContextList($constraint['ctx_constraint_ctx_list']);
            if ($keyword === 'as' && $as === null) {
                $as = $list;
            } elseif ($keyword === 'super' && $super === null) {
                $super = $list;
            } else {
                $this->fail("unsupported second `$keyword` constraint on $name", $constraint);
            }
        }
        $value = $this->parseContextList($node['context_const_ctx_list']);

        if (!$abstract && ($as !== null || $super !== null || $value === null)) {
            $this->fail("context constant $name must be abstract or have a value only", $node);
        }

        return [
            'kind' => 'ctx',
            'name' => $name,
            'abstract' => $abstract,
            'as' => $as,
            'super' => $super,
            'value' => $value,
        ];
    }

    /**
     * @param array<string, mixed> $node a type_parameters node, or missing
     * @return list<array{name: string, variance: string, as: ?array}>
     */
    private function parseTypeParameters(array $node): array
    {
        if (self::isMissing($node)) {
            return [];
        }
        $params = [];
        foreach (self::items($node['type_parameters_parameters']) as $param) {
            $name = self::text($param['type_name']);
            $this->reject($param['type_attribute_spec'], "attributes on $name");
            $this->reject($param['type_reified'], 'reified generic');
            $this->reject($param['type_param_params'], "higher-kinded $name");
            $as = null;
            foreach (self::items($param['type_constraints']) as $constraint) {
                if (strtolower(self::text($constraint['constraint_keyword'])) !== 'as' || $as !== null) {
                    $this->fail("unsupported constraint on $name", $constraint);
                }
                $as = $this->parseType($constraint['constraint_type']);
            }
            $params[] = [
                'name' => $name,
                'variance' => self::isMissing($param['type_variance']) ? '' : self::text($param['type_variance']),
                'as' => $as,
            ];
        }
        return $params;
    }

    /**
     * @param array<string, mixed> $node a list of parameter_declaration, or missing
     * @return list<array<string, mixed>>
     */
    private function parseParameters(array $node, bool $untypedAllowed): array
    {
        $params = [];
        foreach (self::items($node) as $param) {
            if ($param['kind'] !== 'parameter_declaration') {
                $this->fail("unsupported parameter `{$param['kind']}`", $param);
            }
            $this->reject($param['parameter_attribute'], 'parameter attributes');
            // Hack's `readonly` is not PHP's: it is rejected, not translated
            $this->reject($param['parameter_readonly'], 'readonly parameter');
            if (isset($param['parameter_optional'])) {
                $this->reject($param['parameter_optional'], 'optional parameter');
            }
            $type = null;
            if (!self::isMissing($param['parameter_type'])) {
                $type = $this->parseType($param['parameter_type']);
            } elseif (!$untypedAllowed) {
                $this->fail('parameter without a type', $param);
            }

            $variadic = false;
            $name = $param['parameter_name'];
            if ($name['kind'] === 'decorated_expression'
                && self::text($name['decorated_expression_decorator']) === '...'
            ) {
                $variadic = true;
                $name = $name['decorated_expression_expression'];
            }
            if ($name['kind'] !== 'token' || $name['token']['kind'] !== 'variable') {
                $this->fail('unsupported parameter name', $name);
            }

            $default = $param['parameter_default_value'];
            $params[] = [
                'name' => self::text($name),
                'type' => $type,
                'inout' => !self::isMissing($param['parameter_call_convention']),
                'modifiers' => $this->parseModifiers(
                    $param['parameter_visibility'],
                    ['public', 'protected', 'private'],
                ),
                'variadic' => $variadic,
                'default' => self::isMissing($default) ? null : $default['simple_initializer_value'],
            ];
        }
        return $params;
    }

    /**
     * @param array<string, mixed> $node a contexts node, or missing (no context)
     * @return list<array{0: string, 1?: string, 2?: string}>|null context items:
     *     ['cap', name], ['wildcard'], ['ctx', $var], ['this', C], ['const', Class, C]
     */
    private function parseContextList(array $node): ?array
    {
        if (self::isMissing($node)) {
            return null;
        }
        $items = [];
        foreach (self::items($node['contexts_types']) as $item) {
            switch ($item['kind']) {
                case 'simple_type_specifier':
                    $name = self::text($item);
                    $items[] = $name === '_' ? ['wildcard'] : ['cap', strtolower($name)];
                    break;

                case 'function_ctx_type_specifier':
                    $items[] = ['ctx', self::text($item['function_ctx_type_variable'])];
                    break;

                case 'type_constant':
                    $owner = self::text($item['type_constant_left_type']);
                    $constant = self::text($item['type_constant_right_type']);
                    $items[] = strtolower($owner) === 'this' ? ['this', $constant] : ['const', $owner, $constant];
                    break;

                default:
                    $this->fail("unsupported context `{$item['kind']}`", $item);
            }
        }
        return $items;
    }

    /**
     * Type AST nodes: ['named', name, args], ['nullable', t], ['union', ts],
     * ['fun', params, return, context], ['shape', fields, open], ['tuple', ts].
     *
     * @param array<string, mixed> $node
     * @return array<int, mixed>
     */
    private function parseType(array $node): array
    {
        switch ($node['kind']) {
            case 'token':
            case 'qualified_name':
            case 'simple_type_specifier':
                $name = self::text($node);
                if ($name === '_') {
                    $this->fail('unsupported placeholder type `_`', $node);
                }
                return ['named', $name, []];

            case 'generic_type_specifier':
                return [
                    'named',
                    self::text($node['generic_class_type']),
                    array_map(
                        $this->parseType(...),
                        self::items($node['generic_argument_list']['type_arguments_types']),
                    ),
                ];

            case 'vector_type_specifier':
                return ['named', 'vec', [$this->parseType($node['vector_type_type'])]];

            case 'keyset_type_specifier':
                return ['named', 'keyset', [$this->parseType($node['keyset_type_type'])]];

            case 'dictionary_type_specifier':
                return [
                    'named',
                    'dict',
                    array_map($this->parseType(...), self::items($node['dictionary_type_members'])),
                ];

            case 'classname_type_specifier':
                return ['named', 'classname', [$this->parseType($node['classname_type'])]];

            case 'nullable_type_specifier':
                return ['nullable', $this->parseType($node['nullable_type'])];

            case 'like_type_specifier':
                return $this->parseType($node['like_type']);

            case 'union_type_specifier':
                return ['union', array_map($this->parseType(...), self::items($node['union_types']))];

            case 'tuple_type_specifier':
                return ['tuple', array_map($this->parseType(...), self::items($node['tuple_types']))];

            case 'shape_type_specifier':
                $fields = [];
                foreach (self::items($node['shape_type_fields']) as $field) {
                    $key = $field['field_name'];
                    if ($key['kind'] === 'literal') {
                        $key = $key['literal_expression'];
                    }
                    if ($key['kind'] !== 'token' || !in_array(
                        $key['token']['kind'],
                        ['single_quoted_string_literal', 'double_quoted_string_literal', 'decimal_literal'],
                        true,
                    )) {
                        $this->fail('unsupported shape key', $field);
                    }
                    $fields[] = [
                        'key' => self::text($key),
                        'optional' => !self::isMissing($field['field_question']),
                        'type' => $this->parseType($field['field_type']),
                    ];
                }
                return ['shape', $fields, !self::isMissing($node['shape_type_ellipsis'])];

            case 'closure_type_specifier':
                $params = [];
                foreach (self::items($node['closure_parameter_list']) as $param) {
                    if ($param['kind'] !== 'closure_parameter_type_specifier') {
                        $this->fail("unsupported `{$param['kind']}` in a function type", $param);
                    }
                    $this->reject($param['closure_parameter_call_convention'], '`inout` in a function type');
                    $this->reject($param['closure_parameter_readonly'], '`readonly` in a function type');
                    if (isset($param['closure_parameter_optional'])) {
                        $this->reject($param['closure_parameter_optional'], '`optional` in a function type');
                    }
                    $params[] = ['type' => $this->parseType($param['closure_parameter_type']), 'variadic' => false];
                }
                $this->reject($node['closure_readonly_keyword'], '`readonly` function type');
                return [
                    'fun',
                    $params,
                    $this->parseType($node['closure_return_type']),
                    $this->parseContextList($node['closure_contexts']),
                ];
        }

        $this->fail("unsupported type `{$node['kind']}`", $node);
    }

    // ----------------------------------------------------------------- types

    /**
     * @param array<int, mixed> $type
     */
    private function docType(array $type): string
    {
        switch ($type[0]) {
            case 'nullable':
                $inner = $this->docType($type[1]);
                return preg_match('/[|(]/', $inner) ? "null|$inner" : "?$inner";

            case 'union':
                return implode('|', array_map(
                    fn(array $t): string => $t[0] === 'fun' ? '(' . $this->docType($t) . ')' : $this->docType($t),
                    $type[1],
                ));

            case 'tuple':
                return 'list{' . implode(', ', array_map($this->docType(...), $type[1])) . '}';

            case 'shape':
                $fields = [];
                foreach ($type[1] as $field) {
                    $key = $field['key'];
                    if ($key[0] === "'" || $key[0] === '"') {
                        $unquoted = substr($key, 1, -1);
                        $key = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $unquoted) ? $unquoted : $key;
                    }
                    $fields[] = $key . ($field['optional'] ? '?' : '') . ': ' . $this->docType($field['type']);
                }
                if ($type[2]) {
                    $fields[] = '...';
                }
                return 'array{' . implode(', ', $fields) . '}';

            case 'fun':
                $params = array_map(
                    fn(array $p): string => $this->docType($p['type']) . ($p['variadic'] ? '...' : ''),
                    $type[1],
                );
                $purity = $type[3] === null ? '' : '[' . $this->contextType($type[3]) . ']';
                return "Closure$purity(" . implode(', ', $params) . '): ' . $this->docType($type[2]);
        }

        [, $name, $args] = $type;
        $bare = strtolower(ltrim(preg_replace('/^\\\\?HH\\\\/i', '', $name) ?? $name, '\\'));
        $argDocs = array_map($this->docType(...), $args);
        $arity = function (int ...$allowed) use ($name, $args): void {
            if (!in_array(count($args), $allowed, true)) {
                throw new UnexpectedValueException(
                    "{$this->file}: `$name` takes " . implode(' or ', $allowed) . ' type argument(s)',
                );
            }
        };

        switch ($bare) {
            case 'vec':
            case 'varray':
                $arity(1);
                return "list<$argDocs[0]>";
            case 'dict':
            case 'darray':
                $arity(2);
                return "array<$argDocs[0], $argDocs[1]>";
            case 'keyset':
                $arity(1);
                return "array<$argDocs[0], $argDocs[0]>";
            case 'container':
            case 'traversable':
                $arity(1);
                return ($bare === 'container' ? 'array' : 'iterable') . "<$argDocs[0]>";
            case 'keyedcontainer':
            case 'keyedtraversable':
                $arity(2);
                return ($bare === 'keyedcontainer' ? 'array' : 'iterable') . "<$argDocs[0], $argDocs[1]>";
            case 'classname':
            case 'typename':
                $arity(1);
                return "class-string<$argDocs[0]>";
            case 'arraykey':
                return 'array-key';
            case 'num':
                return 'int|float';
            case 'nothing':
            case 'noreturn':
                return 'never';
            case 'dynamic':
                return 'mixed';
            case 'this':
                return 'static';
            case 'nonnull':
            case 'vector':
            case 'map':
            case 'set':
            case 'pair':
            case 'awaitable':
                throw new UnexpectedValueException("{$this->file}: unsupported type `$name`");
        }

        return $name . ($argDocs === [] ? '' : '<' . implode(', ', $argDocs) . '>');
    }

    /**
     * The native PHP type of a Hack type, or null when there is none.
     *
     * @param array<int, mixed> $type
     */
    private function nativeType(array $type, bool $isReturn): ?string
    {
        switch ($type[0]) {
            case 'nullable':
                $inner = $this->nativeType($type[1], false);
                if ($inner === null || $inner === 'mixed' || $inner === 'null') {
                    return $inner;
                }
                return str_contains($inner, '|') ? "$inner|null" : "?$inner";

            case 'union':
                $natives = [];
                foreach ($type[1] as $member) {
                    $native = $this->nativeType($member, false);
                    if ($native === null) {
                        return null;
                    }
                    if ($native === 'mixed') {
                        return 'mixed';
                    }
                    foreach (explode('|', ltrim($native, '?')) as $part) {
                        $natives[strtolower($part)] = $part;
                    }
                    if ($native[0] === '?') {
                        $natives['null'] = 'null';
                    }
                }
                return implode('|', $natives);

            case 'tuple':
            case 'shape':
                return 'array';

            case 'fun':
                return 'Closure';
        }

        [, $name] = $type;
        $bare = strtolower(ltrim(preg_replace('/^\\\\?HH\\\\/i', '', $name) ?? $name, '\\'));
        if (isset($this->templates[$name])) {
            return null;
        }
        return match ($bare) {
            'vec', 'varray', 'dict', 'darray', 'keyset', 'container', 'keyedcontainer' => 'array',
            'traversable', 'keyedtraversable' => 'iterable',
            'classname', 'typename' => 'string',
            'arraykey' => 'int|string',
            'num' => 'int|float',
            'nothing', 'noreturn' => $isReturn ? 'never' : null,
            'this' => $isReturn ? 'static' : null,
            'void' => $isReturn ? 'void' : null,
            'dynamic', 'mixed' => 'mixed',
            'null' => null,
            default => $name,
        };
    }

    // -------------------------------------------------------------- contexts

    /**
     * @param list<string> $caps
     * @return list<string>
     */
    private static function orderCapabilities(array $caps): array
    {
        return array_values(array_filter(
            self::CAPABILITY_ORDER,
            static fn(string $cap): bool => in_array($cap, $caps, true),
        ));
    }

    /**
     * What a Hack context list allows, as Psalm capabilities.
     *
     * @param list<array> $items
     * @return array{
     *     impure: bool,
     *     caps: list<string>,
     *     aliases: list<string>,
     *     templates: list<string>,
     *     dependsOn: list<string>,
     *     wildcard: bool,
     * }
     */
    private function resolveContext(array $items): array
    {
        $result = [
            'impure' => false,
            'caps' => ['read-props'],
            'aliases' => [],
            'templates' => [],
            'dependsOn' => [],
            'wildcard' => false,
        ];

        foreach ($items as $item) {
            switch ($item[0]) {
                case 'cap':
                    if ($item[1] === 'defaults') {
                        $result['impure'] = true;
                    } elseif (isset(self::HACK_CAPABILITIES[$item[1]])) {
                        $result['caps'] = [...$result['caps'], ...self::HACK_CAPABILITIES[$item[1]]];
                    } else {
                        throw new UnexpectedValueException("{$this->file}: unsupported context `{$item[1]}`");
                    }
                    break;

                case 'wildcard':
                    $result['wildcard'] = true;
                    break;

                case 'ctx':
                    $result['dependsOn'][] = $item[1];
                    break;

                case 'this':
                    $declaring = $this->class === null ? null : $this->contextConstant($this->class, $item[1]);
                    if ($declaring === null
                        || !$declaring[1]['abstract']
                        || $declaring[0]['name'] !== $this->class['name']
                    ) {
                        throw new UnexpectedValueException(
                            "{$this->file}: unsupported `this::{$item[1]}` outside the class declaring it abstract",
                        );
                    }
                    $result['templates'][] = $item[1];
                    break;

                case 'const':
                    $owner = strtolower($item[1]) === 'self'
                        ? $this->class
                        : ($this->classes[strtolower($item[1])] ?? null);
                    $declaring = $owner === null ? null : $this->contextConstant($owner, $item[2]);
                    if ($declaring === null || $declaring[1]['abstract']) {
                        throw new UnexpectedValueException(
                            "{$this->file}: unsupported context `{$item[1]}::{$item[2]}`"
                                . ' (not a concrete context constant)',
                        );
                    }
                    [$declaringClass, $constant] = $declaring;
                    if ($this->class === null) {
                        // functions cannot import an alias: inline its value
                        $inlined = $this->resolveContext($constant['value']);
                        $result['impure'] = $result['impure'] || $inlined['impure'];
                        $result['caps'] = [...$result['caps'], ...$inlined['caps']];
                        $result['aliases'] = [...$result['aliases'], ...$inlined['aliases']];
                    } else {
                        if ($declaringClass['name'] !== $this->class['name']) {
                            $this->imports[$item[2]] = "@psalm-import-type {$item[2]} from {$declaringClass['name']}";
                        }
                        $result['aliases'][] = $item[2];
                    }
                    break;
            }
        }

        $result['caps'] = self::orderCapabilities($result['caps']);
        $result['aliases'] = array_values(array_unique($result['aliases']));
        return $result;
    }

    /**
     * The purity of a function type or class template argument.
     *
     * @param list<array> $items
     */
    private function contextType(array $items): string
    {
        $context = $this->resolveContext($items);
        if ($context['dependsOn'] !== []) {
            throw new UnexpectedValueException("{$this->file}: unsupported `ctx \$var` in a type");
        }
        if ($context['wildcard']) {
            if (count($items) !== 1) {
                throw new UnexpectedValueException("{$this->file}: unsupported `_` combined with other contexts");
            }
            if (!$this->wildcardAllowed) {
                // Hack only allows `[_]` in the type of a parameter the function's context names
                throw new UnexpectedValueException(
                    "{$this->file}: `[_]` outside the type of a `ctx \$param` parameter",
                );
            }
            return '_';
        }
        if ($context['templates'] !== []) {
            if (count($items) !== 1) {
                throw new UnexpectedValueException("{$this->file}: unsupported `this::C` combined with other contexts");
            }
            return $context['templates'][0];
        }
        return $context['impure'] ? 'impure' : implode('|', [...$context['aliases'], ...$context['caps']]);
    }

    /**
     * The docblock tags giving a function-like the purity of a Hack context list
     * (null: no context, which is Hack's `[defaults]`).
     *
     * @param list<array>|null $items
     * @param list<array<string, mixed>> $params
     * @return list<string>
     */
    private function contextTags(?array $items, array $params): array
    {
        if ($items === null) {
            return ['@psalm-impure'];
        }
        $context = $this->resolveContext($items);
        if ($context['wildcard']) {
            throw new UnexpectedValueException("{$this->file}: unsupported `_` in a function's context");
        }
        foreach ($context['dependsOn'] as $variable) {
            $param = null;
            foreach ($params as $candidate) {
                if ($candidate['name'] === $variable) {
                    $param = $candidate;
                }
            }
            $type = $param['type'] ?? null;
            while (is_array($type) && $type[0] === 'nullable') {
                $type = $type[1];
            }
            if (!is_array($type) || $type[0] !== 'fun' || $type[3] !== [['wildcard']]) {
                throw new UnexpectedValueException(
                    "{$this->file}: unsupported `ctx $variable` for a parameter that is not a `(function()[_]: ...)`",
                );
            }
        }
        if ($context['impure']) {
            return ['@psalm-impure'];
        }
        $caps = $context['caps'];
        foreach ($params as $param) {
            if ($param['inout']) {
                $caps = self::orderCapabilities([...$caps, 'write-refs']);
            }
        }
        $tags = ['@psalm-capabilities ' . implode('|', [...$context['aliases'], ...$caps])];
        foreach ($context['templates'] as $template) {
            $tags[] = "@psalm-purity-from-template $template";
        }
        return $tags;
    }

    /**
     * The class declaring context constant $name, looking up the hierarchy.
     *
     * @param array<string, mixed> $class
     * @return array{array<string, mixed>, array<string, mixed>}|null
     */
    private function contextConstant(array $class, string $name): ?array
    {
        foreach ($class['members'] as $member) {
            if ($member['kind'] === 'ctx' && $member['name'] === $name) {
                return [$class, $member];
            }
        }
        $parent = $this->parentClass($class);
        return $parent === null ? null : $this->contextConstant($parent, $name);
    }

    /**
     * @param array<string, mixed> $class
     * @return array<string, mixed>|null
     */
    private function parentClass(array $class): ?array
    {
        if ($class['classKind'] !== 'class' || $class['extends'] === []) {
            return null;
        }
        return $this->classes[strtolower(ltrim($class['extends'][0][1], '\\'))] ?? null;
    }

    /**
     * The abstract context constants (purity templates) of a class, in order.
     *
     * @param array<string, mixed> $class
     * @return list<array<string, mixed>>
     */
    private function purityTemplates(array $class): array
    {
        $parent = $this->parentClass($class);
        $templates = $parent === null ? [] : $this->purityTemplates($parent);
        foreach ($class['members'] as $member) {
            if ($member['kind'] === 'ctx' && $member['abstract']) {
                $templates[] = $member;
            }
        }
        return $templates;
    }


    // -------------------------------------------------------------- emitting

    /** @param array<string, mixed> $class */
    private function emitClass(array $class): string
    {
        $this->class = $class;
        $this->imports = [];
        $this->templates = [];
        foreach ($class['templates'] as $template) {
            $this->templates[$template['name']] = true;
        }

        $tags = $this->templateTags($class['templates']);

        $members = [];
        $ownTemplates = [];
        $aliases = [];
        $bound = [];
        foreach ($class['members'] as $member) {
            switch ($member['kind']) {
                case 'use':
                    $members[] = 'use ' . implode(', ', $member['names']) . ';';
                    break;

                case 'const':
                    $members[] = "const {$member['name']} = " . $this->emitValue($member['value']) . ';';
                    break;

                case 'property':
                    $members[] = $this->emitProperty($member);
                    break;

                case 'function':
                    $members[] = $this->emitFunction($member, 1);
                    break;

                case 'ctx':
                    if ($member['abstract']) {
                        $ownTemplates[] = $this->purityTemplateTag($member);
                        break;
                    }
                    $parent = $this->parentClass($class);
                    $overridden = $parent === null ? null : $this->contextConstant($parent, $member['name']);
                    if ($overridden !== null && $overridden[1]['abstract']) {
                        $bound[$member['name']] = $this->contextType($member['value']);
                    } elseif ($overridden !== null) {
                        throw new UnexpectedValueException(
                            "{$this->file}: unsupported override of concrete context constant {$member['name']}",
                        );
                    } else {
                        $aliases[] = "@psalm-type {$member['name']} = " . $this->contextType($member['value']);
                    }
                    break;
            }
        }

        if ($ownTemplates !== []) {
            $tags[] = '@psalm-purity-template ' . implode(', ', $ownTemplates);
        }
        $tags = [...$tags, ...$aliases, ...array_values($this->imports)];

        foreach ($class['extends'] as $parentType) {
            $parent = $class['classKind'] === 'class' ? $this->parentClass($class) : null;
            $purityArgs = '';
            if ($parent !== null && $bound !== []) {
                $args = [];
                foreach ($this->purityTemplates($parent) as $template) {
                    if (isset($bound[$template['name']])) {
                        $args[] = $bound[$template['name']];
                    } elseif ($template['value'] !== null) {
                        $args[] = $this->contextType($template['value']);
                    } else {
                        throw new UnexpectedValueException(
                            "{$this->file}: {$class['name']} does not bind {$template['name']}",
                        );
                    }
                }
                $purityArgs = '[' . implode(', ', $args) . ']';
            }
            if ($purityArgs !== '' || $parentType[2] !== []) {
                $doc = $this->docType($parentType);
                $bracket = strpos($doc, '<');
                $doc = $bracket === false
                    ? $doc . $purityArgs
                    : substr($doc, 0, $bracket) . $purityArgs . substr($doc, $bracket);
                $tags[] = "@extends $doc";
            }
        }
        foreach ($class['implements'] as $interface) {
            if ($interface[2] !== []) {
                $tags[] = '@implements ' . $this->docType($interface);
            }
        }

        $head = implode(' ', [...$class['modifiers'], $class['classKind'], $class['name']]);
        $extends = array_map(fn(array $t): string => $t[1], $class['extends']);
        if ($extends !== []) {
            $head .= ' extends ' . implode(', ', $extends);
        }
        $implements = array_map(fn(array $t): string => $t[1], $class['implements']);
        if ($implements !== []) {
            $head .= ' implements ' . implode(', ', $implements);
        }

        $this->class = null;
        $this->templates = [];

        $body = $members === [] ? ' {}' : " {\n" . self::indent(implode("\n\n", $members)) . "\n}";
        return self::docblock($tags) . $head . $body;
    }

    /** @param array<string, mixed> $template */
    private function purityTemplateTag(array $template): string
    {
        $tag = $template['name'];
        if ($template['value'] !== null) {
            $tag .= '(' . $this->contextType($template['value']) . ')';
        }
        if ($template['super'] !== null) {
            $tag .= ' <= ' . $this->contextType($template['super']);
        }
        if ($template['as'] !== null) {
            $tag = $this->contextType($template['as']) . ' <= ' . $tag;
        }
        return $tag;
    }

    /**
     * @param list<array{name: string, variance: string, as: ?array}> $templates
     * @return list<string>
     */
    private function templateTags(array $templates): array
    {
        $tags = [];
        foreach ($templates as $template) {
            $tag = match ($template['variance']) {
                '+' => '@template-covariant',
                '-' => '@template-contravariant',
                default => '@template',
            } . ' ' . $template['name'];
            if ($template['as'] !== null) {
                $tag .= ' of ' . $this->docType($template['as']);
            }
            $tags[] = $tag;
        }
        return $tags;
    }

    /** @param array<string, mixed> $property */
    private function emitProperty(array $property): string
    {
        $tags = [];
        $parts = $property['modifiers'];
        if ($property['type'] !== null) {
            $native = $this->nativeType($property['type'], false);
            $doc = $this->docType($property['type']);
            if ($native !== $doc) {
                $tags[] = "@var $doc";
            }
            if ($native !== null) {
                $parts[] = $native;
            }
        }
        $parts[] = $property['name'];
        $code = implode(' ', $parts);
        if ($property['default'] !== null) {
            $code .= ' = ' . $this->emitValue($property['default']);
        }
        return self::docblock($tags) . $code . ';';
    }

    /**
     * @param array<string, mixed> $function
     * @param int $depth the nesting of the declaration (1 for a method), whose
     *     indentation its body loses
     */
    private function emitFunction(array $function, int $depth): string
    {
        $outerTemplates = $this->templates;
        foreach ($function['templates'] as $template) {
            $this->templates[$template['name']] = true;
        }

        $contextTags = $this->contextTags($function['context'], $function['params']);
        [$params, $paramTags] = $this->emitParameters($function['params'], $function['context']);
        $tags = [...$this->templateTags($function['templates']), ...$contextTags, ...$paramTags];

        $head = implode(' ', [...$function['modifiers'], 'function', $function['name']]) . "($params)";
        if ($function['return'] !== null) {
            $native = $this->nativeType($function['return'], true);
            $doc = $this->docType($function['return']);
            if ($native !== $doc) {
                $tags[] = "@return $doc";
            }
            if ($native !== null) {
                $head .= ": $native";
            }
        }

        $attributes = '';
        foreach ($function['attributes'] as $attribute) {
            $attributes .= match ($attribute) {
                '__Override' => "#[\\Override]\n",
                '__EntryPoint' => '',
                default => throw new UnexpectedValueException("{$this->file}: unsupported attribute <<$attribute>>"),
            };
        }

        $body = ';';
        if ($function['body'] !== null) {
            $this->depth = $depth;
            // without the line break and indentation after the closing brace
            $body = ' ' . rtrim($this->emitScope($function['body'], self::names($function['params']), $contextTags));
        }

        $this->templates = $outerTemplates;

        return self::docblock($tags) . $attributes . $head . $body;
    }

    /**
     * @param list<array<string, mixed>> $params
     * @param list<array>|null $context the context of the function-like, whose
     *     `ctx $param` parameters may have a `[_]` function type
     * @return array{string, list<string>} the parameter list and its docblock tags
     */
    private function emitParameters(array $params, ?array $context): array
    {
        $dependsOn = [];
        foreach ($context ?? [] as $item) {
            if ($item[0] === 'ctx') {
                $dependsOn[] = $item[1];
            }
        }

        $codes = [];
        $tags = [];
        foreach ($params as $param) {
            $parts = $param['modifiers'];
            $name = ($param['inout'] ? '&' : '') . ($param['variadic'] ? '...' : '') . $param['name'];
            if ($param['type'] !== null) {
                $native = $this->nativeType($param['type'], false);
                $this->wildcardAllowed = in_array($param['name'], $dependsOn, true);
                try {
                    $doc = $this->docType($param['type']);
                } finally {
                    $this->wildcardAllowed = false;
                }
                if ($native !== $doc) {
                    $tags[] = "@param $doc " . ($param['variadic'] ? '...' : '') . $param['name'];
                }
                if ($native !== null) {
                    $parts[] = $native;
                }
            }
            $parts[] = $name;
            $code = implode(' ', $parts);
            if ($param['default'] !== null) {
                $code .= ' = ' . $this->emitValue($param['default']);
            }
            $codes[] = $code;
        }
        return [implode(', ', $codes), $tags];
    }

    // ----------------------------------------------------------- expressions

    /**
     * PHP for a constant expression (a default value, a constant's value).
     *
     * @param array<string, mixed> $node
     */
    private function emitValue(array $node): string
    {
        $depth = $this->depth;
        $this->depth = 0;
        $this->lineStart = false;
        $this->skipLeading = true;
        try {
            return rtrim($this->emit($node));
        } finally {
            $this->depth = $depth;
        }
    }

    /**
     * PHP for the body of a function-like, in which $variables are defined and
     * a lambda without a context gets $contextTags.
     *
     * @param array<string, mixed> $node
     * @param list<string> $variables
     * @param list<string> $contextTags
     */
    private function emitScope(array $node, array $variables, array $contextTags): string
    {
        $outerSeen = $this->seen;
        $this->seen = [];
        foreach ($variables as $variable) {
            $this->seen[$variable] = true;
        }
        $this->contextStack[] = $contextTags;
        $this->lineStart = false;
        $this->skipLeading = true;
        try {
            return $this->emit($node);
        } finally {
            array_pop($this->contextStack);
            $this->seen = $outerSeen;
        }
    }

    /**
     * PHP for a node of a function body: lambdas, Hack arrays and shapes,
     * `inout`/`readonly` markers, `is` tests and function pointers are rewritten,
     * the statements and expressions PHP shares with Hack are copied token by
     * token (with their whitespace and comments, the indentation doubled since
     * fixtures indent by two spaces), anything else is rejected.
     *
     * @param array<string, mixed> $node
     */
    private function emit(array $node): string
    {
        switch ($node['kind']) {
            case 'missing':
                return '';

            case 'token':
                return $this->emitToken($node['token']);

            case 'list':
                $out = '';
                foreach ($node['elements'] as $element) {
                    $out .= $this->emit($element);
                }
                return $out;

            case 'list_item':
                $this->parent = 'list_item';
                return $this->emit($node['list_item']) . $this->emit($node['list_separator']);

            case 'literal':
                $literal = $node['literal_expression'];
                if ($literal['kind'] !== 'token' || !in_array($literal['token']['kind'], self::LITERAL_TOKENS, true)) {
                    $this->fail('unsupported literal (string interpolation, heredoc, ...)', $node);
                }
                return $this->emit($literal);

            case 'lambda_expression':
                return $this->emitLambda($node);

            case 'is_expression':
                return $this->emitIs($node);

            case 'vector_intrinsic_expression':
            case 'dictionary_intrinsic_expression':
                // `vec[...]`, `dict[...]`: PHP arrays
                $prefix = $node['kind'] === 'vector_intrinsic_expression' ? 'vector_intrinsic' : 'dictionary_intrinsic';
                $this->reject($node["{$prefix}_explicit_type"], 'explicit type arguments');
                return $this->dropToken($node["{$prefix}_keyword"])
                    . $this->emit($node["{$prefix}_left_bracket"])
                    . $this->emit($node["{$prefix}_members"])
                    . $this->emit($node["{$prefix}_right_bracket"]);

            case 'shape_expression':
            case 'tuple_expression':
                // `shape('a' => 1)`, `tuple(1, 2)`: PHP arrays
                $prefix = $node['kind'];
                $elements = $prefix === 'shape_expression' ? 'shape_expression_fields' : 'tuple_expression_items';
                return $this->dropToken($node["{$prefix}_keyword"])
                    . $this->replaceToken($node["{$prefix}_left_paren"], '[')
                    . $this->emit($node[$elements])
                    . $this->replaceToken($node["{$prefix}_right_paren"], ']');

            case 'decorated_expression':
                $decorator = strtolower(self::text($node['decorated_expression_decorator']));
                if ($decorator === 'inout' || $decorator === 'readonly') {
                    // `f(inout $x)` is `f($x)` with a by-reference parameter; a
                    // readonly value is a plain one (Psalm sees where it comes from)
                    return $this->dropToken($node['decorated_expression_decorator'], true)
                        . $this->emit($node['decorated_expression_expression']);
                }
                if ($decorator !== '...') {
                    $this->fail("unsupported `$decorator`", $node);
                }
                return $this->emitChildren($node);

            case 'prefix_unary_expression':
                $operator = strtolower(self::text($node['prefix_unary_operator']));
                if ($operator === 'readonly') {
                    // a readonly value is a plain one (Psalm sees where it comes from)
                    return $this->dropToken($node['prefix_unary_operator'], true)
                        . $this->emit($node['prefix_unary_operand']);
                }
                if (in_array($operator, ['await', 'suspend'], true)) {
                    $this->fail("unsupported `$operator`", $node);
                }
                return $this->emitChildren($node);

            case 'function_pointer_expression':
                // `f<>`, `C::f<>`: first-class callables
                $typeArgs = $node['function_pointer_type_args'];
                if (self::items($typeArgs['type_arguments_types']) !== []) {
                    $this->fail('unsupported function pointer with type arguments', $node);
                }
                return $this->emit($node['function_pointer_receiver'])
                    . $this->dropToken($typeArgs['type_arguments_left_angle'], true)
                    . $this->replaceToken($typeArgs['type_arguments_right_angle'], '(...)', false);

            case 'function_call_expression':
                $this->reject($node['function_call_type_args'], 'explicit type arguments');
                $receiver = $node['function_call_receiver'];
                $function = $receiver['kind'] === 'token' ? strtolower($receiver['token']['text']) : null;
                if ($function === 'vec') {
                    return $this->replaceToken($receiver, 'array_values')
                        . $this->emitChildren($node, ['function_call_receiver']);
                }
                if (in_array($function, ['dict', 'keyset', 'varray', 'darray', 'tuple', 'shape'], true)) {
                    $this->fail("unsupported `$function()`", $node);
                }
                return $this->emitChildren($node);

            case 'object_creation_expression':
                return $this->emitChildren($node);

            case 'constructor_call':
                if ($node['constructor_call_type']['kind'] === 'generic_type_specifier') {
                    $this->fail('unsupported explicit type arguments', $node);
                }
                return $this->emitChildren($node);

            case 'foreach_statement':
                $this->reject($node['foreach_await_keyword'], '`await as`');
                return $this->emitChildren($node);
        }

        if (!in_array($node['kind'], self::VERBATIM_NODES, true)) {
            $this->fail("unsupported `{$node['kind']}`", $node);
        }
        return $this->emitChildren($node);
    }

    /**
     * @param array<string, mixed> $node
     * @param list<string> $skip fields not to emit
     */
    private function emitChildren(array $node, array $skip = []): string
    {
        $out = '';
        foreach ($node as $field => $child) {
            if ($field !== 'kind' && !in_array($field, $skip, true)) {
                $this->parent = $node['kind'];
                $out .= $this->emit($child);
            }
        }
        return $out;
    }

    /** @param array<string, mixed> $token */
    private function emitToken(array $token): string
    {
        if (in_array($token['kind'], ['|>', '$$'], true)) {
            $this->fail("unsupported `{$token['text']}`", ['kind' => 'token', 'token' => $token]);
        }
        if ($token['kind'] === 'variable') {
            $this->seen[$token['text']] = true;
        }
        $out = $this->leading($token);
        $out .= $token['text'];
        if ($token['text'] !== '') {
            $this->lineStart = false;
        }
        return $out . $this->trivia($token['trailing']);
    }

    /**
     * The leading trivia of a token, unless the token starts what is emitted.
     *
     * @param array<string, mixed> $token
     */
    private function leading(array $token): string
    {
        if ($this->skipLeading) {
            $this->skipLeading = false;
            return '';
        }
        return $this->trivia($token['leading']);
    }

    /**
     * A token's trivia only, without its text (and its trailing trivia too unless
     * $dropTrailing is false).
     *
     * @param array<string, mixed> $node a token node
     */
    private function dropToken(array $node, bool $dropTrailing = false): string
    {
        $out = $this->leading($node['token']);
        return $dropTrailing ? $out : $out . $this->trivia($node['token']['trailing']);
    }

    /**
     * A token with different text, keeping its trivia.
     *
     * @param array<string, mixed> $node a token node
     */
    private function replaceToken(array $node, string $text, bool $leading = true): string
    {
        $out = $leading ? $this->leading($node['token']) : '';
        $this->lineStart = false;
        return $out . $text . $this->trivia($node['token']['trailing']);
    }

    /** @param list<array<string, mixed>> $trivia */
    private function trivia(array $trivia): string
    {
        $out = '';
        foreach ($trivia as $piece) {
            switch ($piece['kind']) {
                case 'end_of_line':
                    $out .= "\n";
                    $this->lineStart = true;
                    break;

                case 'whitespace':
                    $out .= $this->lineStart
                        ? str_repeat(' ', max(0, 2 * strlen($piece['text']) - 4 * $this->depth))
                        : $piece['text'];
                    $this->lineStart = false;
                    break;

                case 'single_line_comment':
                case 'delimited_comment':
                    $out .= $piece['text'];
                    $this->lineStart = false;
                    break;

                default:
                    throw new UnexpectedValueException("{$this->file}: unsupported `{$piece['kind']}` in the source");
            }
        }
        return $out;
    }

    /**
     * `$x is T`: an `instanceof` test, or a null test.
     *
     * @param array<string, mixed> $node an is_expression
     */
    private function emitIs(array $node): string
    {
        // `!$x is T` is `!($x is T)`: parenthesize the test unless it stands alone
        $alone = in_array(
            $this->parent,
            ['parenthesized_expression', 'if_statement', 'while_statement', 'return_statement', 'list_item'],
            true,
        );
        $type = $node['is_right_operand'];
        $name = $type['kind'] === 'simple_type_specifier' ? self::text($type) : null;
        if ($name === null || (in_array(strtolower($name), self::NON_CLASS_TYPES, true)
            && !in_array(strtolower($name), ['null', 'nonnull'], true))
        ) {
            $this->fail('unsupported `is` test', $type);
        }

        $leading = $this->leading(self::firstToken($node['is_left_operand']) ?? []);
        $left = $this->emit($node['is_left_operand']);
        $operator = $node['is_operator']['token'];
        $test = match (strtolower($name)) {
            'null' => '=== null',
            'nonnull' => '!== null',
            default => "instanceof $name",
        };
        $out = $left . $this->trivia($operator['leading']) . $test;
        $typeToken = self::firstToken($type) ?? [];
        $trailing = $this->trivia($typeToken['trailing'] ?? []);

        return $leading . ($alone ? $out : "($out)") . $trailing;
    }

    /**
     * `$x ==> ...` and `(params)[ctx]: T ==> ...`: an arrow function, or a
     * closure capturing the variables of the enclosing scope it uses.
     *
     * @param array<string, mixed> $node a lambda_expression
     */
    private function emitLambda(array $node): string
    {
        $this->reject($node['lambda_attribute_spec'], 'lambda attributes');
        $this->reject($node['lambda_async'], 'async lambda');
        $leading = $this->leading(self::firstToken($node) ?? []);

        $signature = $node['lambda_signature'];
        if ($signature['kind'] === 'token' && $signature['token']['kind'] === 'variable') {
            $params = [[
                'name' => $signature['token']['text'],
                'type' => null,
                'inout' => false,
                'modifiers' => [],
                'variadic' => false,
                'default' => null,
            ]];
            $context = null;
            $return = null;
        } elseif ($signature['kind'] === 'lambda_signature') {
            $params = $this->parseParameters($signature['lambda_parameters'], true);
            $context = $this->parseContextList($signature['lambda_contexts']);
            $return = self::isMissing($signature['lambda_type']) ? null : $this->parseType($signature['lambda_type']);
        } else {
            $this->fail("unsupported lambda signature `{$signature['kind']}`", $signature);
        }

        // a lambda without a context has the context of the function-like around it
        $contextTags = $context === null
            ? (end($this->contextStack) ?: ['@psalm-impure'])
            : $this->contextTags($context, $params);
        [$paramCode, $paramTags] = $this->emitParameters($params, $context);
        $tags = [...$contextTags, ...$paramTags];

        $returnCode = '';
        if ($return !== null) {
            $native = $this->nativeType($return, true);
            $doc = $this->docType($return);
            if ($native !== $doc) {
                $tags[] = "@return $doc";
            }
            if ($native !== null) {
                $returnCode = ": $native";
            }
        }
        $docblock = '/** ' . implode(' ', $tags) . ' */ ';

        $body = $node['lambda_body'];
        $paramNames = self::names($params);
        if ($body['kind'] !== 'compound_statement') {
            $code = $this->emitScope($body, [...array_keys($this->seen), ...$paramNames], $contextTags);
            return "$leading{$docblock}fn($paramCode)$returnCode => $code";
        }

        $captures = [];
        foreach (self::variables($body) as $variable) {
            if ($variable !== '$this' && isset($this->seen[$variable]) && !in_array($variable, $paramNames, true)) {
                $captures[$variable] = true;
            }
        }
        $captures = array_keys($captures);
        $use = $captures === [] ? '' : ' use (' . implode(', ', $captures) . ')';
        $code = $this->emitScope($body, [...$captures, ...$paramNames], $contextTags);

        return "$leading{$docblock}function ($paramCode)$use$returnCode $code";
    }

    /**
     * The variables a node uses, in order.
     *
     * @param array<string, mixed> $node
     * @return list<string>
     */
    private static function variables(array $node): array
    {
        if (($node['kind'] ?? null) === 'token') {
            return $node['token']['kind'] === 'variable' ? [$node['token']['text']] : [];
        }
        $variables = [];
        foreach ($node as $key => $child) {
            if ($key !== 'kind' && is_array($child)) {
                $variables = array_merge($variables, self::variables($child));
            }
        }
        return $variables;
    }

    /**
     * @param list<array<string, mixed>> $params
     * @return list<string>
     */
    private static function names(array $params): array
    {
        return array_map(static fn(array $param): string => $param['name'], $params);
    }

    /** @param list<string> $tags */
    private static function docblock(array $tags): string
    {
        if ($tags === []) {
            return '';
        }
        if (count($tags) === 1) {
            return "/** $tags[0] */\n";
        }
        return "/**\n" . implode('', array_map(static fn(string $tag): string => " * $tag\n", $tags)) . " */\n";
    }

    private static function indent(string $code): string
    {
        return preg_replace('/^(?=.)/m', '    ', $code) ?? $code;
    }
}
