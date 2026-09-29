<?php

/**
 * Transpiles a Hack conformance fixture into PHP with Psalm docblocks, so that the
 * verdict HHVM gives a fixture can be asserted of Psalm on the same program.
 *
 * It covers the subset of Hack the fixtures are written in, not Hack as a whole:
 * classes, functions, generics, function types, contexts and context constants,
 * lambdas, Hack arrays and shapes, `inout`, `readonly`, `is` and function
 * pointers. Anything else is reported as a UnexpectedValueException naming the
 * construct, rather than translated wrongly; extend the transpiler when a new
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
 * An `inout` parameter is a by-reference parameter, which Psalm charges
 * `write-refs` for writing (writing one is no effect in Hack). Abstract context
 * constants are class purity templates (`as` is the lower bound, `super` the
 * upper one), and a subclass binding one is an `@extends Parent[...]`.
 *
 * CLI only (lives under bin/, which Psalm does not analyse); see transpile.php.
 */

declare(strict_types=1);

namespace Psalm\HackConformance;

use UnexpectedValueException;

use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_shift;
use function array_splice;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function ltrim;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function substr_count;
use function token_get_all;

use const T_ABSTRACT;
use const T_ARRAY;
use const T_CALLABLE;
use const T_CLASS;
use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_CURLY_OPEN;
use const T_DOC_COMMENT;
use const T_DOLLAR_OPEN_CURLY_BRACES;
use const T_ELLIPSIS;
use const T_FINAL;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_IS_EQUAL;
use const T_IS_GREATER_OR_EQUAL;
use const T_IS_NOT_EQUAL;
use const T_LNUMBER;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_READONLY;
use const T_REQUIRE;
use const T_SL;
use const T_STATIC;
use const T_STRING;
use const T_USE;
use const T_VARIABLE;
use const T_WHITESPACE;

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

    private const TRIVIA = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    /** Hack types an `is` test cannot turn into `instanceof` */
    private const NON_CLASS_TYPES = [
        'int', 'string', 'bool', 'float', 'num', 'arraykey', 'mixed', 'dynamic', 'nothing',
        'vec', 'dict', 'keyset', 'shape', 'tuple',
    ];

    /** @var list<array{0: int|string, 1: string}> */
    private array $t = [];

    private int $i = 0;

    /** @var array<string, array<string, mixed>> classes by lowercase name */
    private array $classes = [];

    /** @var array<string, mixed>|null the class being emitted */
    private ?array $class = null;

    /** @var array<string, true> template names in scope */
    private array $templates = [];

    /** @var array<string, string> `@psalm-import-type` lines the class being emitted needs */
    private array $imports = [];

    private function __construct(private readonly string $file)
    {
    }

    /**
     * @return string PHP code, starting with `<?php`
     */
    public static function transpile(string $hack, string $file = 'fixture'): string
    {
        return (new self($file))->run($hack);
    }

    private function run(string $hack): string
    {
        $tokens = token_get_all("<?php\n" . $hack);
        array_shift($tokens);
        foreach ($tokens as $token) {
            $this->t[] = is_array($token) ? [$token[0], $token[1]] : [$token, $token];
        }

        $decls = [];
        while ($this->peekKind() !== null) {
            $decls[] = $this->parseTopLevel();
        }

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

    // ---------------------------------------------------------------- tokens

    private function fail(string $message, ?int $at = null): never
    {
        $at ??= $this->skipTrivia($this->i);
        $line = 1;
        for ($k = 0; $k < $at && $k < count($this->t); $k++) {
            $line += substr_count($this->t[$k][1], "\n");
        }
        $near = $at < count($this->t) ? " near `{$this->t[$at][1]}`" : ' at end of file';
        throw new UnexpectedValueException("{$this->file}:$line: $message$near");
    }

    private function skipTrivia(int $k): int
    {
        while ($k < count($this->t) && in_array($this->t[$k][0], self::TRIVIA, true)) {
            $k++;
        }
        return $k;
    }

    private function peekKind(int $ahead = 0): int|string|null
    {
        $k = $this->skipTrivia($this->i);
        while ($ahead-- > 0) {
            $k = $this->skipTrivia($k + 1);
        }
        return $this->t[$k][0] ?? null;
    }

    private function peekText(int $ahead = 0): ?string
    {
        $k = $this->skipTrivia($this->i);
        while ($ahead-- > 0) {
            $k = $this->skipTrivia($k + 1);
        }
        return $this->t[$k][1] ?? null;
    }

    private function peekIs(string $text, int $ahead = 0): bool
    {
        $peeked = $this->peekText($ahead);
        return $peeked !== null && strtolower($peeked) === $text;
    }

    /** @return array{0: int|string, 1: string} */
    private function next(): array
    {
        $this->i = $this->skipTrivia($this->i);
        if ($this->i >= count($this->t)) {
            $this->fail('unexpected end of file');
        }
        return $this->t[$this->i++];
    }

    private function expect(string $text): void
    {
        if ($text === '>') {
            $this->splitGreaterThan();
        }
        if (!$this->peekIs($text)) {
            $this->fail("expected `$text`");
        }
        $this->next();
    }

    private function accept(string $text): bool
    {
        if ($text === '>') {
            $this->splitGreaterThan();
        }
        if ($this->peekIs($text)) {
            $this->next();
            return true;
        }
        return false;
    }

    /**
     * `vec<vec<int>>` lexes its closing brackets as one `>>` token (and `>=`
     * likewise): split it where a type expects a `>`.
     */
    private function splitGreaterThan(): void
    {
        $k = $this->skipTrivia($this->i);
        $text = $this->t[$k][1] ?? '';
        if (strlen($text) < 2 || $text[0] !== '>' || ($text !== '>>' && $text !== '>=' && $text !== '>>=')) {
            return;
        }
        $rest = substr($text, 1);
        $restToken = match ($rest) {
            '>' => ['>', '>'],
            '=' => ['=', '='],
            '>=' => [T_IS_GREATER_OR_EQUAL, '>='],
        };
        array_splice($this->t, $k, 1, [['>', '>'], $restToken]);
    }

    private function name(): string
    {
        $kind = $this->peekKind();
        if ($kind !== T_STRING && $kind !== T_NAME_QUALIFIED && $kind !== T_NAME_FULLY_QUALIFIED) {
            $this->fail('expected a name');
        }
        return $this->next()[1];
    }

    private function variable(): string
    {
        if ($this->peekKind() !== T_VARIABLE) {
            $this->fail('expected a variable');
        }
        return $this->next()[1];
    }

    /**
     * Index of the token closing the bracket opened at $open.
     */
    private function matching(int $open): int
    {
        $pairs = ['(' => ')', '[' => ']', '{' => '}'];
        $close = $pairs[$this->t[$open][1]];
        $depth = 0;
        for ($k = $open; $k < count($this->t); $k++) {
            $text = $this->t[$k][1];
            $kind = $this->t[$k][0];
            if (isset($pairs[$text]) || $kind === T_CURLY_OPEN || $kind === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            } elseif ($text === ')' || $text === ']' || $text === '}') {
                $depth--;
                if ($depth === 0) {
                    if ($text !== $close) {
                        $this->fail("unbalanced `{$this->t[$open][1]}`", $open);
                    }
                    return $k;
                }
            }
        }
        $this->fail("unclosed `{$this->t[$open][1]}`", $open);
    }

    // --------------------------------------------------------------- parsing

    /** @return array<string, mixed> */
    private function parseTopLevel(): array
    {
        $attributes = $this->parseAttributes();
        $modifiers = $this->parseModifiers();

        $kind = $this->peekKind();
        if ($kind === T_FUNCTION) {
            if ($modifiers !== []) {
                $this->fail('unexpected modifiers on a function');
            }
            return $this->parseFunction($attributes, []);
        }
        if ($kind === T_CLASS || $kind === T_INTERFACE) {
            return $this->parseClass($attributes, $modifiers);
        }
        $this->fail('unsupported top-level declaration');
    }

    /** @return list<string> */
    private function parseAttributes(): array
    {
        $attributes = [];
        while ($this->peekKind() === T_SL) {
            $this->next();
            do {
                $name = $this->name();
                if ($this->peekIs('(')) {
                    $this->fail("unsupported attribute arguments on $name");
                }
                $attributes[] = $name;
            } while ($this->accept(','));
            $this->splitGreaterThan();
            $this->expect('>');
            $this->expect('>');
        }
        return $attributes;
    }

    /** @return list<string> */
    private function parseModifiers(): array
    {
        $modifiers = [];
        $kinds = [T_ABSTRACT, T_FINAL, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_READONLY];
        while (in_array($this->peekKind(), $kinds, true)) {
            $modifiers[] = strtolower($this->next()[1]);
        }
        return $modifiers;
    }

    /**
     * @param list<string> $attributes
     * @param list<string> $modifiers
     * @return array<string, mixed>
     */
    private function parseFunction(array $attributes, array $modifiers): array
    {
        $this->expect('function');
        $name = $this->name();
        $templates = $this->peekIs('<') ? $this->parseTypeParameters() : [];
        $params = $this->parseParameters(false);
        $context = $this->peekIs('[') ? $this->parseContextList() : null;
        $return = $this->accept(':') ? $this->parseType() : null;

        $body = null;
        if ($this->peekIs('{')) {
            $open = $this->skipTrivia($this->i);
            $close = $this->matching($open);
            $body = [$open, $close];
            $this->i = $close + 1;
        } else {
            $this->expect(';');
        }

        return [
            'kind' => 'function',
            'attributes' => $attributes,
            'modifiers' => $modifiers,
            'name' => $name,
            'templates' => $templates,
            'params' => $params,
            'context' => $context,
            'return' => $return,
            'body' => $body,
        ];
    }

    /**
     * @param list<string> $attributes
     * @param list<string> $modifiers
     * @return array<string, mixed>
     */
    private function parseClass(array $attributes, array $modifiers): array
    {
        if ($attributes !== []) {
            $this->fail('unsupported class attributes <<' . implode(', ', $attributes) . '>>');
        }
        if (in_array('abstract', $modifiers, true) && in_array('final', $modifiers, true)) {
            $this->fail('unsupported `abstract final` class');
        }

        $classKind = strtolower($this->next()[1]);
        $name = $this->name();
        $templates = $this->peekIs('<') ? $this->parseTypeParameters() : [];

        $extends = [];
        $implements = [];
        while (true) {
            if ($this->accept('extends')) {
                do {
                    $extends[] = $this->parseType();
                } while ($this->accept(','));
            } elseif ($this->accept('implements')) {
                do {
                    $implements[] = $this->parseType();
                } while ($this->accept(','));
            } else {
                break;
            }
        }

        $this->expect('{');
        $members = [];
        while (!$this->accept('}')) {
            $members[] = $this->parseMember();
        }

        return [
            'kind' => 'class',
            'classKind' => $classKind,
            'modifiers' => $modifiers,
            'name' => $name,
            'templates' => $templates,
            'extends' => $extends,
            'implements' => $implements,
            'members' => $members,
        ];
    }

    /** @return array<string, mixed> */
    private function parseMember(): array
    {
        if ($this->peekKind() === T_USE) {
            $this->next();
            $names = [];
            do {
                $names[] = $this->name();
            } while ($this->accept(','));
            $this->expect(';');
            return ['kind' => 'use', 'names' => $names];
        }
        if ($this->peekKind() === T_REQUIRE) {
            $this->fail('unsupported `require` constraint');
        }

        $attributes = $this->parseAttributes();
        $modifiers = $this->parseModifiers();

        if ($this->peekKind() === T_FUNCTION) {
            return $this->parseFunction($attributes, $modifiers);
        }
        if ($attributes !== []) {
            $this->fail('unsupported attributes <<' . implode(', ', $attributes) . '>>');
        }

        if ($this->accept('const')) {
            if ($this->peekIs('ctx')) {
                $this->next();
                return $this->parseContextConstant($modifiers);
            }
            if ($this->peekIs('type')) {
                $this->fail('unsupported type constant');
            }
            if (in_array('abstract', $modifiers, true)) {
                $this->fail('unsupported abstract constant');
            }
            // `const int X = 1;`: the type is optional in Hack, and dropped
            if ($this->peekKind(1) !== '=') {
                $this->parseType();
            }
            $name = $this->name();
            $this->expect('=');
            $value = $this->expressionUntil([';']);
            $this->expect(';');
            return ['kind' => 'const', 'name' => $name, 'value' => $value];
        }

        $type = $this->peekKind() === T_VARIABLE ? null : $this->parseType();
        $name = $this->variable();
        $default = null;
        if ($this->accept('=')) {
            $default = $this->expressionUntil([';']);
        }
        $this->expect(';');
        return [
            'kind' => 'property',
            'modifiers' => $modifiers,
            'type' => $type,
            'name' => $name,
            'default' => $default,
        ];
    }

    /**
     * @param list<string> $modifiers
     * @return array<string, mixed>
     */
    private function parseContextConstant(array $modifiers): array
    {
        $name = $this->name();
        $as = null;
        $super = null;
        $value = null;
        while (true) {
            if ($this->accept('as')) {
                $as = $this->parseContextList();
            } elseif ($this->accept('super')) {
                $super = $this->parseContextList();
            } elseif ($this->accept('=')) {
                $value = $this->parseContextList();
            } else {
                break;
            }
        }
        $this->expect(';');

        $abstract = in_array('abstract', $modifiers, true);
        if (!$abstract && ($as !== null || $super !== null || $value === null)) {
            $this->fail("context constant $name must be abstract or have a value only");
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
     * Token range of an expression running up to one of $terminators at depth 0.
     *
     * @param list<string> $terminators
     * @return array{int, int}
     */
    private function expressionUntil(array $terminators): array
    {
        $start = $this->i;
        $k = $this->i;
        while ($k < count($this->t)) {
            $text = $this->t[$k][1];
            if (in_array($text, $terminators, true)) {
                $this->i = $k;
                return [$start, $k];
            }
            if ($text === '(' || $text === '[' || $text === '{') {
                $k = $this->matching($k);
            }
            $k++;
        }
        $this->fail('unterminated expression', $start);
    }

    /** @return list<array{name: string, variance: string, as: ?array}> */
    private function parseTypeParameters(): array
    {
        $this->expect('<');
        $params = [];
        do {
            if ($this->peekIs('>')) {
                break;
            }
            $variance = '';
            if ($this->peekIs('+') || $this->peekIs('-')) {
                $variance = $this->next()[1];
            }
            if ($this->peekIs('reify')) {
                $this->fail('unsupported reified generic');
            }
            $name = $this->name();
            $as = null;
            while (true) {
                if ($this->accept('as')) {
                    if ($as !== null) {
                        $this->fail("unsupported second `as` constraint on $name");
                    }
                    $as = $this->parseType();
                } elseif ($this->peekIs('super')) {
                    $this->fail("unsupported `super` constraint on $name");
                } else {
                    break;
                }
            }
            $params[] = ['name' => $name, 'variance' => $variance, 'as' => $as];
        } while ($this->accept(','));
        $this->expect('>');
        return $params;
    }

    /** @return list<array<string, mixed>> */
    private function parseParameters(bool $untypedAllowed): array
    {
        $this->expect('(');
        $params = [];
        while (!$this->accept(')')) {
            $params[] = $this->parseParameter($untypedAllowed);
            if (!$this->peekIs(')')) {
                $this->expect(',');
            }
        }
        return $params;
    }

    /** @return array<string, mixed> */
    private function parseParameter(bool $untypedAllowed): array
    {
        $attributes = $this->parseAttributes();
        if ($attributes !== []) {
            $this->fail('unsupported parameter attributes');
        }
        $inout = $this->accept('inout');
        $modifiers = $this->parseModifiers();
        if ($this->peekIs('optional')) {
            $this->fail('unsupported optional parameter');
        }
        $type = null;
        if ($this->peekKind() !== T_VARIABLE && $this->peekKind() !== T_ELLIPSIS) {
            $type = $this->parseType();
        } elseif (!$untypedAllowed) {
            $this->fail('parameter without a type');
        }
        $variadic = $this->accept('...');
        $name = $this->variable();
        $default = null;
        if ($this->accept('=')) {
            $default = $this->expressionUntil([',', ')']);
        }
        return [
            'name' => $name,
            'type' => $type,
            'inout' => $inout,
            'modifiers' => $modifiers,
            'variadic' => $variadic,
            'default' => $default,
        ];
    }

    /**
     * @return list<array{0: string, 1?: string, 2?: string}> context items:
     *     ['cap', name], ['wildcard'], ['ctx', $var], ['this', C], ['const', Class, C]
     */
    private function parseContextList(): array
    {
        $this->expect('[');
        $items = [];
        while (!$this->accept(']')) {
            if ($this->accept('_')) {
                $items[] = ['wildcard'];
            } elseif ($this->peekIs('ctx') && $this->peekKind(1) === T_VARIABLE) {
                $this->next();
                $items[] = ['ctx', $this->variable()];
            } else {
                $name = $this->name();
                if ($this->accept('::')) {
                    $constant = $this->name();
                    $items[] = strtolower($name) === 'this' ? ['this', $constant] : ['const', $name, $constant];
                } else {
                    $items[] = ['cap', strtolower($name)];
                }
            }
            if (!$this->peekIs(']')) {
                $this->expect(',');
            }
        }
        return $items;
    }

    /**
     * Type AST nodes: ['named', name, args], ['nullable', t], ['union', ts],
     * ['fun', params, return, context], ['shape', fields, open], ['tuple', ts].
     *
     * @return array<int, mixed>
     */
    private function parseType(): array
    {
        if ($this->accept('?')) {
            return ['nullable', $this->parseType()];
        }
        if ($this->accept('~') || $this->accept('readonly')) {
            return $this->parseType();
        }
        if ($this->accept('(')) {
            if ($this->accept('function')) {
                return $this->parseFunctionType();
            }
            $first = $this->parseType();
            foreach (['|' => 'union', ',' => 'tuple'] as $separator => $kind) {
                if ($this->peekIs($separator)) {
                    $types = [$first];
                    while ($this->accept($separator)) {
                        if ($this->peekIs(')')) {
                            break;
                        }
                        $types[] = $this->parseType();
                    }
                    $this->expect(')');
                    return [$kind, $types];
                }
            }
            if ($this->peekIs('&')) {
                $this->fail('unsupported intersection type');
            }
            $this->expect(')');
            return $first;
        }

        $kind = $this->peekKind();
        $nameKinds = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_ARRAY, T_STATIC, T_CALLABLE];
        if (!in_array($kind, $nameKinds, true)) {
            $this->fail('expected a type');
        }
        $name = $this->next()[1];
        if (strtolower($name) === 'shape') {
            return $this->parseShape();
        }
        if ($this->peekIs('::')) {
            $this->fail('unsupported type constant');
        }
        $args = [];
        if ($this->accept('<')) {
            while (!$this->accept('>')) {
                $args[] = $this->parseType();
                if (!$this->peekIs('>')) {
                    $this->splitGreaterThan();
                    if (!$this->peekIs('>')) {
                        $this->expect(',');
                    }
                }
            }
        }
        return ['named', $name, $args];
    }

    /** @return array<int, mixed> */
    private function parseFunctionType(): array
    {
        $this->expect('(');
        $params = [];
        while (!$this->accept(')')) {
            if ($this->peekIs('inout') || $this->peekIs('optional')) {
                $this->fail('unsupported `' . (string) $this->peekText() . '` in a function type');
            }
            $type = $this->parseType();
            $params[] = ['type' => $type, 'variadic' => $this->accept('...')];
            if (!$this->peekIs(')')) {
                $this->expect(',');
            }
        }
        $context = $this->peekIs('[') ? $this->parseContextList() : null;
        $this->expect(':');
        $return = $this->parseType();
        $this->expect(')');
        return ['fun', $params, $return, $context];
    }

    /** @return array<int, mixed> */
    private function parseShape(): array
    {
        $this->expect('(');
        $fields = [];
        $open = false;
        while (!$this->accept(')')) {
            if ($this->accept('...')) {
                $open = true;
            } else {
                $optional = $this->accept('?');
                if ($this->peekKind() !== T_CONSTANT_ENCAPSED_STRING && $this->peekKind() !== T_LNUMBER) {
                    $this->fail('unsupported shape key');
                }
                $key = $this->next()[1];
                $this->expect('=>');
                $fields[] = ['key' => $key, 'optional' => $optional, 'type' => $this->parseType()];
            }
            if (!$this->peekIs(')')) {
                $this->expect(',');
            }
        }
        return ['shape', $fields, $open];
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
                    $members[] = "const {$member['name']} = " . $this->transformExpression($member['value'], []) . ';';
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
            $code .= ' = ' . $this->transformExpression($property['default'], []);
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

        $tags = [
            ...$this->templateTags($function['templates']),
            ...$this->contextTags($function['context'], $function['params']),
        ];
        [$params, $paramTags] = $this->emitParameters($function['params']);
        $tags = [...$tags, ...$paramTags];

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

        $scope = array_map(static fn(array $p): string => $p['name'], $function['params']);
        $body = $function['body'] === null
            ? ';'
            : ' ' . str_replace(
                "\n" . str_repeat('    ', $depth),
                "\n",
                $this->transformExpression([$function['body'][0], $function['body'][1] + 1], $scope),
            );

        $this->templates = $outerTemplates;

        return self::docblock($tags) . $attributes . $head . $body;
    }

    /**
     * @param list<array<string, mixed>> $params
     * @return array{string, list<string>} the parameter list and its docblock tags
     */
    private function emitParameters(array $params): array
    {
        $codes = [];
        $tags = [];
        foreach ($params as $param) {
            $parts = $param['modifiers'];
            $name = ($param['inout'] ? '&' : '') . ($param['variadic'] ? '...' : '') . $param['name'];
            if ($param['type'] !== null) {
                $native = $this->nativeType($param['type'], false);
                $doc = $this->docType($param['type']);
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
                $code .= ' = ' . $this->transformExpression($param['default'], []);
            }
            $codes[] = $code;
        }
        return [implode(', ', $codes), $tags];
    }

    /**
     * PHP for the Hack tokens in [$start, $end): lambdas, Hack arrays and shapes,
     * `inout`/`readonly` markers, `is` tests and function pointers are rewritten,
     * the rest (whitespace included) is copied, with the indentation doubled
     * (fixtures indent by two spaces).
     *
     * @param array{int, int} $range
     * @param list<string> $scope variables defined before the range
     */
    private function transformExpression(array $range, array $scope): string
    {
        [$start, $end] = $range;
        $start = $this->skipTrivia($start);
        $out = '';
        /** @var array<int, string> $replacements closing brackets to rewrite */
        $replacements = [];
        $seen = array_fill_keys($scope, true);

        for ($k = $start; $k < $end; $k++) {
            [$kind, $text] = $this->t[$k];

            if (isset($replacements[$k])) {
                $out .= $replacements[$k];
                continue;
            }

            if ($kind === T_WHITESPACE) {
                $out .= preg_replace('/\n( +)/', "\n$1$1", $text);
                continue;
            }

            if ($text === '(' || $kind === T_VARIABLE) {
                $lambda = $this->lambdaAt($k, $end);
                if ($lambda !== null) {
                    [$code, $resume] = $this->emitLambda($lambda, array_keys($seen));
                    $out .= $code;
                    $k = $resume - 1;
                    continue;
                }
            }

            if ($kind === T_VARIABLE) {
                $seen[$text] = true;
            }

            $next = $k + 1 < $end ? $this->t[$k + 1] : null;
            $lower = strtolower($text);

            $call = $kind === T_STRING && $next !== null && $next[1] === '(';
            $literal = $kind === T_STRING && $next !== null && $next[1] === '[';

            if ($literal && in_array($lower, ['vec', 'dict', 'varray', 'darray'], true)) {
                continue;
            }
            if ($literal && $lower === 'keyset') {
                $this->fail('unsupported keyset literal', $k);
            }
            if ($call && in_array($lower, ['shape', 'tuple'], true)) {
                $replacements[$this->matching($k + 1)] = ']';
                $out .= '[';
                $k++;
                continue;
            }
            if ($call && $lower === 'vec') {
                $out .= 'array_values';
                continue;
            }
            if ($kind === T_STRING && $next !== null && $next[0] === T_IS_NOT_EQUAL) {
                // `f<>`: a function pointer
                $out .= "$text(...)";
                $k++;
                continue;
            }
            if (($kind === T_STRING && $lower === 'inout') || $kind === T_READONLY) {
                $k = $this->skipTrivia($k + 1) - 1;
                continue;
            }
            if ($kind === T_STRING && $lower === 'is') {
                $target = $this->skipTrivia($k + 1);
                $type = strtolower($this->t[$target][1]);
                $out .= match (true) {
                    $type === 'nonnull' => '!== null',
                    $type === 'null' => '=== null',
                    in_array($type, self::NON_CLASS_TYPES, true) => $this->fail('unsupported `is` test', $target),
                    default => 'instanceof ' . $this->t[$target][1],
                };
                $k = $target;
                continue;
            }
            if ($kind === T_IS_EQUAL && $next !== null && $next[1] === '>') {
                $this->fail('unsupported lambda', $k);
            }
            if (in_array($lower, ['await', 'async', 'using', 'concurrent', 'invariant'], true) && $kind === T_STRING) {
                $this->fail("unsupported `$text`", $k);
            }

            $out .= $text;
        }

        return $out;
    }

    /**
     * A lambda starting at $k: `$x ==> ...` or `(params)[ctx]: T ==> ...`.
     *
     * @return array<string, mixed>|null
     */
    private function lambdaAt(int $k, int $end): ?array
    {
        $saved = [$this->i, $this->t];
        $this->i = $k;
        try {
            if ($this->t[$k][0] === T_VARIABLE) {
                $params = [[
                    'name' => $this->next()[1],
                    'type' => null,
                    'inout' => false,
                    'modifiers' => [],
                    'variadic' => false,
                    'default' => null,
                ]];
                $context = null;
                $return = null;
            } else {
                $params = $this->parseParameters(true);
                $context = $this->peekIs('[') ? $this->parseContextList() : null;
                $return = $this->accept(':') ? $this->parseType() : null;
            }
            $arrow = $this->skipTrivia($this->i);
            if ($arrow + 1 >= $end || $this->t[$arrow][0] !== T_IS_EQUAL || $this->t[$arrow + 1][1] !== '>') {
                [$this->i, $this->t] = $saved;
                return null;
            }
            $this->i = $arrow + 2;
        } catch (UnexpectedValueException) {
            [$this->i, $this->t] = $saved;
            return null;
        }
        $bodyStart = $this->skipTrivia($this->i);
        $lambda = ['params' => $params, 'context' => $context, 'return' => $return, 'bodyStart' => $bodyStart];
        $this->i = $saved[0];
        return $lambda;
    }

    /**
     * @param array<string, mixed> $lambda
     * @param list<string> $scope
     * @return array{string, int} the PHP up to the lambda's body, and where the body starts
     */
    private function emitLambda(array $lambda, array $scope): array
    {
        $tags = [];
        if ($lambda['context'] !== null) {
            $tags = $this->contextTags($lambda['context'], $lambda['params']);
        }
        [$params, $paramTags] = $this->emitParameters($lambda['params']);
        $tags = [...$tags, ...$paramTags];

        $return = '';
        if ($lambda['return'] !== null) {
            $native = $this->nativeType($lambda['return'], true);
            $doc = $this->docType($lambda['return']);
            if ($native !== $doc) {
                $tags[] = "@return $doc";
            }
            if ($native !== null) {
                $return = ": $native";
            }
        }

        $docblock = $tags === [] ? '' : '/** ' . implode(' ', $tags) . ' */ ';
        $bodyStart = $lambda['bodyStart'];

        if ($this->t[$bodyStart][1] !== '{') {
            return ["{$docblock}fn($params)$return => ", $bodyStart];
        }

        $paramNames = array_map(static fn(array $p): string => $p['name'], $lambda['params']);
        $captures = [];
        for ($k = $bodyStart; $k <= $this->matching($bodyStart); $k++) {
            $variable = $this->t[$k][1];
            if ($this->t[$k][0] === T_VARIABLE && $variable !== '$this'
                && in_array($variable, $scope, true) && !in_array($variable, $paramNames, true)
            ) {
                $captures[$variable] = true;
            }
        }
        $use = $captures === [] ? '' : ' use (' . implode(', ', array_keys($captures)) . ')';

        return ["{$docblock}function ($params)$use$return ", $bodyStart];
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
