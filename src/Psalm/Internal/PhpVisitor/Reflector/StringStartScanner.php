<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use ReflectionProperty;

use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function min;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * Records, while a class is scanned, how the strings some of its members hold start, for the names of the keyed stores
 * (see TaintStore), which are often built by them: what a method made of one return returns (`return "lock_$id";`),
 * what a private or readonly property gets from its default and its constructor only (`private string $key =
 * self::KEY;`), what the private methods of the class are called with in it, and what the class gives the
 * constructors of the classes it creates. They are kept in the storage of the class, as the scan may run in other
 * processes than the analysis.
 *
 * A start is a list of parts: `L` and a literal, `C` and a class constant (`Class::NAME`), `R` and a property of
 * `$this`, `N` and the offset of an argument of the constructor, then `M` where something the scan doesn't know
 * follows.
 *
 * @internal
 */
final class StringStartScanner
{
    /** Between the parts of a start, and between the starts of a property */
    private const PART_SEPARATOR = "\0";
    private const START_SEPARATOR = "\1";

    /** Between the starts of the arguments of a call */
    private const ARGUMENT_SEPARATOR = "\2";

    /** What the scan doesn't know */
    private const MORE = 'M';

    /** A `new` whose arguments the scan can't tell the parameters of */
    private const UNKNOWN_CALL = '?';

    /**
     * The properties and constructor arguments being resolved, whose starts can depend on each other: one met again
     * is not known
     *
     * @var array<string, true>
     */
    private static array $resolving = [];

    public static function scan(ClassLike $class, ClassLikeStorage $storage, Aliases $aliases): void
    {
        $self = $storage->name;
        $encoded = self::getCallArguments($aliases, $self, $class);
        $properties = self::getKnownProperties($class);
        $starts = [];

        foreach ($class->getMethods() as $method) {
            $stmts = $method->stmts ?? [];
            if (count($stmts) === 1 && $stmts[0] instanceof Return_ && $stmts[0]->expr !== null) {
                $encoded['m:' . $method->name->toLowerString()] = implode(
                    self::PART_SEPARATOR,
                    self::getStart($aliases, $self, $stmts[0]->expr),
                );
            }

            $is_constructor = $method->name->toLowerString() === '__construct';
            foreach ((new NodeFinder())->findInstanceOf($stmts, Assign::class) as $assign) {
                $property = self::getOwnPropertyName($assign->var);
                if ($property === null) {
                    continue;
                }

                if ($is_constructor) {
                    $starts[$property][] = self::getStart($aliases, $self, $assign->expr);
                } else {
                    // set again later: it may hold anything
                    unset($properties[$property]);
                }
            }

            if (!$is_constructor) {
                continue;
            }

            foreach ($method->params as $offset => $param) {
                $encoded['d:' . $offset] = implode(
                    self::PART_SEPARATOR,
                    $param->default === null ? [self::MORE] : self::getStart($aliases, $self, $param->default),
                );

                if ($param->isPromoted() && $param->var instanceof Variable && is_string($param->var->name)) {
                    // what the constructor is called with, or the default
                    $starts[$param->var->name][] = ['N' . $offset];
                }
            }
        }

        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $item) {
                // a property without type nor default starts null; a typed one without default is set by the
                // constructor
                if ($item->default !== null) {
                    $starts[$item->name->toString()][] = self::getStart($aliases, $self, $item->default);
                } elseif ($property->type === null) {
                    $starts[$item->name->toString()][] = [self::MORE];
                }
            }
        }

        foreach ($starts as $property => $property_starts) {
            if (!isset($properties[$property])) {
                continue;
            }

            $encoded_starts = [];
            foreach ($property_starts as $start) {
                $encoded_starts[] = implode(self::PART_SEPARATOR, $start);
            }

            $encoded['p:' . $property] = implode(self::START_SEPARATOR, $encoded_starts);
        }

        // a start of nothing known is the same as none recorded: the storages of every class keep these
        foreach ($encoded as $key => $value) {
            if (strpos($key, 'new:') !== 0 && in_array(self::MORE, explode(self::START_SEPARATOR, $value), true)) {
                unset($encoded[$key]);
            }
        }

        $storage->string_starts = $encoded;
    }

    /**
     * The start of what the method returns, null if the scan didn't record it
     */
    public static function getMethodStart(Codebase $codebase, string $class, string $method): ?string
    {
        $storage = self::getStorage($codebase, $class);
        $encoded = $storage?->string_starts['m:' . strtolower($method)] ?? null;

        return $storage === null || $encoded === null ? null : self::resolve($codebase, $storage->name, $encoded);
    }

    /**
     * What all the arguments the parameter of the method can be given start with, null if the scan didn't record them:
     * those of the private methods, called in their class, and of the constructors, called by `new`
     */
    public static function getParameterStart(Codebase $codebase, string $class, string $method, int $offset): ?string
    {
        if (strtolower($method) === '__construct') {
            return self::getConstructorArgumentStart($codebase, $class, $offset);
        }

        $storage = self::getStorage($codebase, $class);
        $encoded = $storage?->string_starts['a:' . strtolower($method) . ':' . $offset] ?? null;

        return $storage === null || $encoded === null
            ? null
            : self::getCommonStartOf($codebase, explode(self::START_SEPARATOR, $encoded), $storage->name);
    }

    /**
     * What all the values the property can hold start with, null if the scan didn't record it
     */
    public static function getPropertyStart(Codebase $codebase, string $class, string $property): ?string
    {
        $storage = self::getStorage($codebase, $class);
        $encoded = $storage?->string_starts['p:' . $property] ?? null;
        if ($storage === null || $encoded === null) {
            return null;
        }

        $resolving_id = $storage->name . '::$' . $property;
        if (isset(self::$resolving[$resolving_id])) {
            return '';
        }

        self::$resolving[$resolving_id] = true;
        try {
            return self::getCommonStartOf($codebase, explode(self::START_SEPARATOR, $encoded), $storage->name);
        } finally {
            unset(self::$resolving[$resolving_id]);
        }
    }

    /**
     * @psalm-pure
     */
    public static function getCommonStart(string $first, string $second): string
    {
        $length = 0;
        $max_length = min(strlen($first), strlen($second));
        while ($length < $max_length && $first[$length] === $second[$length]) {
            $length++;
        }

        return substr($first, 0, $length);
    }

    /**
     * What the arguments the constructor of the class is given by the `new` of the classes scanned start with, null if
     * no `new` calls it
     */
    private static function getConstructorArgumentStart(Codebase $codebase, string $class, int $offset): ?string
    {
        $storage = self::getStorage($codebase, $class);
        if ($storage === null) {
            return null;
        }

        $resolving_id = $storage->name . '::__construct#' . $offset;
        if (isset(self::$resolving[$resolving_id])) {
            return '';
        }

        self::$resolving[$resolving_id] = true;
        try {
            $default = $storage->string_starts['d:' . $offset] ?? '';
            $common = null;
            foreach (self::getNews($codebase, $storage->name) as [$self, $call]) {
                if ($call === self::UNKNOWN_CALL) {
                    return '';
                }

                // an argument is resolved in the class giving it, the default in the class created
                $arguments = $call === '' ? [] : explode(self::ARGUMENT_SEPARATOR, $call);
                $start = isset($arguments[$offset])
                    ? self::resolve($codebase, $self, $arguments[$offset])
                    : self::resolve($codebase, $storage->name, $default);
                $common = $common === null ? $start : self::getCommonStart($common, $start);
            }

            return $common;
        } finally {
            unset(self::$resolving[$resolving_id]);
        }
    }

    /**
     * The `new` of the class in the classes scanned: the class making each, and the starts of its arguments (see
     * getNewArguments())
     *
     * @return list<array{string, string}>
     */
    private static function getNews(Codebase $codebase, string $class): array
    {
        if ($codebase->constructor_argument_starts === null) {
            $news = [];
            foreach (ClassLikeStorageProvider::getAll() as $storage) {
                foreach ($storage->string_starts as $key => $calls) {
                    if (strpos($key, 'new:') !== 0) {
                        continue;
                    }

                    foreach (explode(self::START_SEPARATOR, $calls) as $call) {
                        $news[substr($key, 4)][] = [$storage->name, $call];
                    }
                }
            }

            $codebase->constructor_argument_starts = $news;
        }

        return $codebase->constructor_argument_starts[strtolower($class)] ?? [];
    }

    /**
     * @param list<string> $starts
     */
    private static function getCommonStartOf(Codebase $codebase, array $starts, string $self): string
    {
        $common = null;
        foreach ($starts as $start) {
            $resolved = self::resolve($codebase, $self, $start);
            $common = $common === null ? $resolved : self::getCommonStart($common, $resolved);
        }

        return (string) $common;
    }

    /**
     * What the private methods of the class are called with in it (`a:method:offset`: the starts of the arguments of
     * each call), unless the class may call them otherwise (a string or a first-class callable naming them, an unpacked
     * argument), and what the class gives the constructors of the classes it creates (`new:class`: the calls, each the
     * starts of its arguments)
     *
     * @return array<string, string>
     */
    private static function getCallArguments(Aliases $aliases, string $self, ClassLike $class): array
    {
        $private = [];
        foreach ($class->getMethods() as $method) {
            if ($method->isPrivate()) {
                $private[$method->name->toLowerString()] = $method;
            }
        }

        $calls = [];
        (new NodeFinder())->find(
            $class->stmts,
            static function (Node $node) use (&$private, &$calls): bool {
                if ($node instanceof String_) {
                    // named otherwise than by a call: called with anything
                    unset($private[strtolower($node->value)]);
                } elseif ($node instanceof CallLike) {
                    $calls[] = $node;
                }

                return false;
            },
        );

        $arguments = [];
        $news = [];
        foreach ($calls as $call) {
            if ($call instanceof New_ && $call->class instanceof Name) {
                $is_self = in_array($call->class->toLowerString(), ['self', 'static'], true);
                $target = strtolower(
                    $is_self ? $self : ClassLikeAnalyzer::getFQCLNFromNameObject($call->class, $aliases),
                );
                $news[$target][] = self::getNewArguments($aliases, $self, $call);
                continue;
            }

            $name = self::getOwnCallName($call);
            if ($name === null || !isset($private[$name])) {
                continue;
            }

            $method = $private[$name];
            if ($call->isFirstClassCallable()) {
                unset($private[$name]);
                continue;
            }

            foreach (array_values($method->params) as $offset => $param) {
                $argument = self::getArgument($call->getArgs(), $offset, $param);
                if ($argument === false) {
                    unset($private[$name]);
                    continue 2;
                }

                $given = $argument ?? $param->default;
                $start = $given === null ? [self::MORE] : self::getStart($aliases, $self, $given);
                $arguments['a:' . $name . ':' . $offset][] = implode(self::PART_SEPARATOR, $start);
            }
        }

        $encoded = [];
        foreach ($arguments as $key => $starts) {
            [, $method] = explode(':', $key);
            if (isset($private[$method])) {
                $encoded[$key] = implode(self::START_SEPARATOR, $starts);
            }
        }

        foreach ($news as $target => $target_calls) {
            $encoded['new:' . $target] = implode(self::START_SEPARATOR, $target_calls);
        }

        return $encoded;
    }

    /**
     * The name of the method of the class the call calls on `$this`, `self` or `static`
     *
     * @psalm-mutation-free
     */
    private static function getOwnCallName(CallLike $call): ?string
    {
        if ($call instanceof MethodCall
            && $call->var instanceof Variable
            && $call->var->name === 'this'
            && $call->name instanceof Identifier
        ) {
            return $call->name->toLowerString();
        }

        if ($call instanceof StaticCall
            && $call->class instanceof Name
            && in_array($call->class->toLowerString(), ['self', 'static'], true)
            && $call->name instanceof Identifier
        ) {
            return $call->name->toLowerString();
        }

        return null;
    }

    /**
     * The argument given to the parameter, null when it gets its default, false when the scan can't tell
     *
     * @param array<array-key, Arg> $args
     * @psalm-mutation-free
     */
    private static function getArgument(array $args, int $offset, Param $param): Expr|false|null
    {
        foreach ($args as $index => $arg) {
            if ($arg->unpack) {
                return false;
            }

            if ($arg->name === null
                ? $index === $offset
                : ($param->var instanceof Variable && $arg->name->toString() === $param->var->name)
            ) {
                return $arg->value;
            }
        }

        return null;
    }

    /**
     * The starts of the arguments of the `new`, separated by ARGUMENT_SEPARATOR, or UNKNOWN_CALL when the scan can't
     * tell which parameters they are given to
     */
    private static function getNewArguments(Aliases $aliases, string $self, New_ $call): string
    {
        $arguments = [];
        foreach ($call->getArgs() as $arg) {
            if ($arg->unpack || $arg->name !== null) {
                return self::UNKNOWN_CALL;
            }

            $arguments[] = implode(self::PART_SEPARATOR, self::getStart($aliases, $self, $arg->value));
        }

        return implode(self::ARGUMENT_SEPARATOR, $arguments);
    }

    /**
     * The properties only the class can set: private or readonly
     *
     * @return array<string, true>
     */
    private static function getKnownProperties(ClassLike $class): array
    {
        $properties = [];
        foreach ($class->getProperties() as $property) {
            if ($property->isPrivate() || $property->isReadonly()) {
                foreach ($property->props as $item) {
                    $properties[$item->name->toString()] = true;
                }
            }
        }

        foreach ($class->getMethod('__construct')?->params ?? [] as $param) {
            if (($param->isPrivate() || $param->isReadonly())
                && $param->var instanceof Variable
                && is_string($param->var->name)
            ) {
                $properties[$param->var->name] = true;
            }
        }

        return $properties;
    }

    /**
     * @psalm-mutation-free
     */
    private static function getOwnPropertyName(Expr $expr): ?string
    {
        if ($expr instanceof PropertyFetch
            && $expr->var instanceof Variable
            && $expr->var->name === 'this'
            && $expr->name instanceof Identifier
        ) {
            return $expr->name->toString();
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function getStart(Aliases $aliases, string $self, Expr $expr): array
    {
        if ($expr instanceof String_) {
            return ['L' . $expr->value];
        }

        if ($expr instanceof ClassConstFetch && $expr->class instanceof Name && $expr->name instanceof Identifier) {
            $is_self = in_array($expr->class->toLowerString(), ['self', 'static'], true);
            $class = $is_self ? $self : ClassLikeAnalyzer::getFQCLNFromNameObject($expr->class, $aliases);

            return ['C' . $class . '::' . $expr->name->toString()];
        }

        if ($expr instanceof Concat) {
            return self::concat(
                self::getStart($aliases, $self, $expr->left),
                self::getStart($aliases, $self, $expr->right),
            );
        }

        $property = self::getOwnPropertyName($expr);
        if ($property !== null) {
            return ['R' . $property];
        }

        if ($expr instanceof InterpolatedString) {
            $start = [];
            foreach ($expr->parts as $part) {
                $start = self::concat(
                    $start,
                    $part instanceof InterpolatedStringPart
                        ? ['L' . $part->value]
                        : self::getStart($aliases, $self, $part),
                );
            }

            return $start;
        }

        if ($expr instanceof FuncCall
            && $expr->name instanceof Name
            && !$expr->isFirstClassCallable()
            && $expr->name->toLowerString() === 'sprintf'
        ) {
            $args = $expr->getArgs();
            if (isset($args[0]) && $args[0]->value instanceof String_) {
                return self::getSprintfStart($aliases, $self, $args[0]->value->value, $args);
            }
        }

        return [self::MORE];
    }

    /**
     * The format up to its conversions, with what they put in: only `%s` puts its argument as it is
     *
     * @param array<array-key, Arg> $args
     * @return list<string>
     */
    private static function getSprintfStart(Aliases $aliases, string $self, string $format, array $args): array
    {
        $start = [];
        for ($argument = 1;; $argument++) {
            $conversion = strpos($format, '%');
            if ($conversion === false) {
                return self::concat($start, ['L' . $format]);
            }

            $start = self::concat($start, ['L' . substr($format, 0, $conversion)]);
            if (substr($format, $conversion, 2) !== '%s' || !isset($args[$argument])) {
                return self::concat($start, [self::MORE]);
            }

            $start = self::concat($start, self::getStart($aliases, $self, $args[$argument]->value));
            $format = substr($format, $conversion + 2);
        }
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     * @return list<string>
     * @psalm-pure
     */
    private static function concat(array $left, array $right): array
    {
        if (in_array(self::MORE, $left, true)) {
            return $left;
        }

        foreach ($right as $part) {
            $left[] = $part;
        }

        return $left;
    }

    private static function resolve(Codebase $codebase, string $self, string $encoded): string
    {
        $start = '';
        foreach (explode(self::PART_SEPARATOR, $encoded) as $part) {
            if ($part === '' || $part === self::MORE) {
                return $start;
            }

            if ($part[0] === 'L') {
                $start .= substr($part, 1);
                continue;
            }

            if ($part[0] === 'R') {
                return $start . (self::getPropertyStart($codebase, $self, substr($part, 1)) ?? '');
            }

            if ($part[0] === 'N') {
                return $start . (self::getConstructorArgumentStart($codebase, $self, (int) substr($part, 1)) ?? '');
            }

            $constant = explode('::', substr($part, 1));
            if (count($constant) !== 2) {
                return $start;
            }

            $type = $codebase->classOrInterfaceOrEnumExists($constant[0])
                ? $codebase->classlikes->getClassConstantType(
                    $constant[0],
                    $constant[1],
                    ReflectionProperty::IS_PRIVATE,
                )
                : null;
            if ($type === null || !$type->isSingleStringLiteral()) {
                return $start;
            }

            $start .= $type->getSingleStringLiteral()->value;
        }

        return $start;
    }

    private static function getStorage(Codebase $codebase, string $class): ?ClassLikeStorage
    {
        return $codebase->classlike_storage_provider->has($class)
            ? $codebase->classlike_storage_provider->get($class)
            : null;
    }
}
