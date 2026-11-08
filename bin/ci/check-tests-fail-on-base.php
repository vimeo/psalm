<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Checks that every test case a pull request adds or changes fails on the pull request's base.
 *
 * Each such case runs alone with the head's tests/ against the merge base's src/, stubs/ and the rest (the "base"), and
 * must pass on the head and fail on the base. A case that passes on the base asserts nothing about the change, unless
 * a `// guard: <reason>` comment sits directly above its provider entry (or above its test method, docblock included).
 *
 * Usage: php bin/ci/check-tests-fail-on-base.php --base=<rev> [--head=<rev>] [--timeout=120] [--max-cases=60] [--list]
 */

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

const ROOT = __DIR__ . '/../..';
const TEST_ATTRIBUTE = 'PHPUnit\Framework\Attributes\Test';
const DATA_PROVIDER_ATTRIBUTE = 'PHPUnit\Framework\Attributes\DataProvider';
const TAINT_PROVIDERS = ['providerValidCodeParse', 'providerInvalidCodeParse'];

require ROOT . '/vendor/autoload.php';

libxml_use_internal_errors(true);

$active_process = null;

/** Every command runs through here, so that an interrupt can stop the one that is running. */
function execute(Process $process, bool $must = false): Process
{
    global $active_process;

    $active_process = $process;
    try {
        $must ? $process->mustRun() : $process->run();
    } finally {
        $active_process = null;
    }

    return $process;
}

/**
 * @param list<string> $command
 * @param array<string, string> $env
 */
function must(array $command, string $cwd = ROOT, array $env = []): string
{
    return execute(new Process($command, $cwd, $env === [] ? null : $env, null, null), true)->getOutput();
}

function show(string $rev, string $path): ?string
{
    $process = new Process(['git', 'show', "$rev:$path"], ROOT);

    return execute($process)->isSuccessful() ? $process->getOutput() : null;
}

function slice(string $code, Node $from, Node $to): string
{
    return substr($code, $from->getStartFilePos(), $to->getEndFilePos() - $from->getStartFilePos() + 1);
}

function quote(string $text): string
{
    return preg_quote($text, '/');
}

function emit(string $text): void
{
    echo $text;
    $summary_file = getenv('GITHUB_STEP_SUMMARY');
    if ($summary_file !== false && $summary_file !== '') {
        file_put_contents($summary_file, $text, FILE_APPEND);
    }
}

function annotate(string $path, int $line, string $message): void
{
    if (getenv('GITHUB_ACTIONS') !== false) {
        echo "::error file=$path,line=$line::" . str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $message) . "\n";
    }
}

/**
 * @param list<string> $header
 * @param list<list<string>> $rows
 */
function table(array $header, array $rows): string
{
    $cell = static fn(string $text): string => str_replace(['|', "\n"], ['\\|', ' '], $text);
    $lines = ['| ' . implode(' | ', $header) . ' |', '|' . str_repeat(' --- |', count($header))];
    foreach ($rows as $row) {
        $lines[] = '| ' . implode(' | ', array_map($cell, $row)) . ' |';
    }

    return implode("\n", $lines) . "\n";
}

/** @return ?string the reason of the `// guard:` comment directly above the node, looking over other comments such as a docblock */
function guardOf(Node $node): ?string
{
    $next = $node->getStartLine();
    foreach (array_reverse($node->getComments()) as $comment) {
        if ($comment->getEndLine() < $next - 1) {
            break;
        }

        if (preg_match('~^//\s*guard:\s*(\S.*?)\s*$~', $comment->getText(), $matches) === 1) {
            return $matches[1];
        }

        $next = $comment->getStartLine();
    }

    return null;
}

/**
 * The string-keyed items of the arrays a provider returns or yields from, and its `yield 'name' => [...]`.
 *
 * @return list<array{Node, Node, Node}> key and value nodes, and the node that carries the comments above the item
 */
function providerItems(ClassMethod $method): array
{
    $visitor = new class extends NodeVisitorAbstract {
        /** @var list<array{Node, Node, Node}> */
        public array $items = [];

        public function enterNode(Node $node): ?int
        {
            // a closure's return is not the provider's
            if ($node instanceof FunctionLike || $node instanceof ClassLike) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            $pairs = match (true) {
                ($node instanceof Return_ || $node instanceof YieldFrom) && $node->expr instanceof Array_
                    => array_map(static fn($item): array => [$item->key, $item->value, $item], $node->expr->items),
                $node instanceof Expression && $node->expr instanceof Yield_
                    => [[$node->expr->key, $node->expr->value, $node]],
                default => [],
            };
            foreach ($pairs as [$key, $value, $carrier]) {
                if ($key instanceof String_ && $value !== null) {
                    $this->items[] = [$key, $value, $carrier];
                }
            }

            return null;
        }
    };

    (new NodeTraverser($visitor))->traverse($method->stmts ?? []);

    return $visitor->items;
}

/**
 * Provider entries and test methods are items alike: `id` tells them apart by kind and name, `value` is the text
 * without the name, so that a renamed item is not a changed one.
 *
 * @return array{
 *     items: list<array{id: string, name: string, text: string, value: string, line: int, class: string, provider: ?string, parameterized: bool, guard: ?string}>,
 *     types: array<string, array{traits: list<string>, consumers: array<string, list<string>>}>
 * }
 */
function analyse(string $code): array
{
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    $stmts = (new NodeTraverser(new NameResolver()))->traverse($parser->parse($code) ?? []);

    $types = [];
    $named_providers = [];
    $declared = [];
    $classes = (new NodeFinder())->find($stmts, static fn(Node $node): bool => $node instanceof Class_ || $node instanceof Trait_);
    foreach ($classes as $type) {
        if ($type->namespacedName === null) {
            continue;
        }

        $class = $type->namespacedName->toString();
        $types[$class] = ['traits' => [], 'consumers' => []];
        foreach ($type->getTraitUses() as $use) {
            foreach ($use->traits as $trait) {
                $types[$class]['traits'][] = $trait->toString();
            }
        }

        foreach ($type->getMethods() as $method) {
            $is_test = str_starts_with($method->name->toString(), 'test');
            $parameterized = false;
            foreach ($method->attrGroups as $group) {
                foreach ($group->attrs as $attr) {
                    $argument = $attr->args[0]->value ?? null;
                    if ($attr->name->toString() === TEST_ATTRIBUTE) {
                        $is_test = true;
                    } elseif ($attr->name->toString() === DATA_PROVIDER_ATTRIBUTE && $argument instanceof String_) {
                        $parameterized = true;
                        $named_providers[$argument->value] = true;
                        $types[$class]['consumers'][$argument->value][] = $method->name->toString();
                    }
                }
            }

            if (!$type instanceof Trait_ && $method->stmts !== null) {
                $declared[] = [$class, $method, $is_test, $parameterized];
            }
        }
    }

    $items = [];
    $add = static function (string $kind, Node $node, Node $carrier, string $name, string $text, string $value, string $class, ?string $provider, bool $parameterized) use (&$items): void {
        $items[] = [
            'id' => "$kind:$class:$provider:$name",
            'name' => $name,
            'text' => $text,
            'value' => $kind . $value,
            'line' => $node->getStartLine(),
            'class' => $class,
            'provider' => $provider,
            'parameterized' => $parameterized,
            'guard' => guardOf($carrier),
        ];
    };
    foreach ($declared as [$class, $method, $is_test, $parameterized]) {
        $name = $method->name->toString();
        $is_provider = str_starts_with($name, 'provide') || isset($named_providers[$name]);
        foreach ($is_provider ? providerItems($method) : [] as [$key, $value, $carrier]) {
            $add('e', $key, $carrier, $key->value, slice($code, $key, $value), slice($code, $value, $value), $class, $name, false);
        }

        if ($is_test) {
            $text = slice($code, $method, $method);
            $name_at = $method->name->getStartFilePos() - $method->getStartFilePos();
            $value = substr($text, 0, $name_at) . substr($text, $name_at + strlen($name));
            $add('m', $method, $method, $name, $text, $value, $class, null, $parameterized);
        }
    }

    return ['items' => $items, 'types' => $types];
}

/** @return array{traits: list<string>, consumers: array<string, list<string>>}|null */
function typeOf(string $class, string $head): ?array
{
    static $analysed = [];

    if (!isset($analysed[$class])) {
        $code = str_starts_with($class, 'Psalm\\Tests\\')
            ? show($head, 'tests/' . str_replace('\\', '/', substr($class, strlen('Psalm\\Tests\\'))) . '.php')
            : null;
        $analysed[$class] = $code === null ? [] : analyse($code)['types'];
    }

    return $analysed[$class][$class] ?? null;
}

/**
 * The test methods fed by a provider, searched in the class and in the traits it uses.
 *
 * @param array<string, array{traits: list<string>, consumers: array<string, list<string>>}> $types
 * @return list<string>
 */
function consumersOf(string $class, string $provider, array $types, string $head): array
{
    $info = $types[$class] ?? typeOf($class, $head) ?? ['traits' => [], 'consumers' => []];
    $consumers = $info['consumers'][$provider] ?? [];
    foreach ($info['traits'] as $trait) {
        $consumers = [...$consumers, ...consumersOf($trait, $provider, $types, $head)];
    }

    return array_values(array_unique($consumers));
}

/**
 * Every filter is a raw regex, as PHPUnit uses a filter that starts with a non-alphanumeric and is a valid regex
 * verbatim: the `@name` syntax is case-insensitive and a # in a name breaks it.
 *
 * @param array{name: string, line: int, class: string, provider: ?string, guard: ?string} $item
 * @param array<string, array{traits: list<string>, consumers: array<string, list<string>>}> $types
 * @return array{case: string, path: string, line: int, guard: ?string, dir: string, regex: string}
 */
function caseOf(array $item, string $path, array $types, string $head): array
{
    $case = ['path' => $path, 'line' => $item['line'], 'guard' => $item['guard'], 'dir' => $path];
    if ($item['provider'] === null) {
        return $case + ['case' => $item['name'] . '$', 'regex' => '/^' . quote($item['class']) . '::' . quote($item['name']) . '$/'];
    }

    // PHP turns a numeric string key into an int, and PHPUnit names such a data set by its index
    $int_key = (string) (int) $item['name'] === $item['name'];
    $data_set = ' with data set ' . ($int_key ? "#{$item['name']}" : '"' . quote($item['name']) . '"') . '$/';
    if ($path === 'tests/TaintTest.php' && in_array($item['provider'], TAINT_PROVIDERS, true)) {
        return [
            'case' => 'tests/Taint @' . $item['name'],
            'dir' => 'tests/Taint',
            'regex' => '/^Psalm\\\\Tests\\\\Taint\\\\TaintCases\d+Test::test' . ($item['provider'] === 'providerValidCodeParse' ? 'Valid' : 'Invalid')
                . 'Code(?:TrackingUnusedVariables)?' . $data_set,
        ] + $case;
    }

    $consumers = consumersOf($item['class'], $item['provider'], $types, $head);

    return $case + [
        'case' => (count($consumers) > 1 ? '(' . implode('|', $consumers) . ')' : ($consumers[0] ?? '\w+')) . ($int_key ? '#' : '@') . $item['name'],
        'regex' => '/^' . quote($item['class']) . '::(' . ($consumers === [] ? '\w+' : implode('|', array_map('quote', $consumers))) . ')' . $data_set,
    ];
}

function statusOf(string $dir, string $path, string $regex, float $timeout): string
{
    $junit = tempnam(sys_get_temp_dir(), 'tests-fail-on-base-junit');
    $process = new Process(
        [PHP_BINARY, 'vendor/bin/phpunit', $path, '--filter', $regex, '--log-junit', $junit, '--no-progress'],
        $dir,
        null,
        null,
        $timeout,
    );
    try {
        execute($process);
        $xml = simplexml_load_file($junit);
        if ($xml === false) {
            return preg_match('/No tests (found|executed)/', $process->getOutput()) === 1 ? 'ERROR' : 'CRASH';
        }

        $suite = $xml->testsuite;
        $tests = (int) $suite['tests'];
        if ($tests === 0) {
            return 'ERROR';
        }

        if ((int) $suite['skipped'] === $tests) {
            return 'SKIPPED';
        }

        return (int) $suite['failures'] + (int) $suite['errors'] > 0 || $process->getExitCode() !== 0 ? 'FAIL' : 'PASS';
    } catch (ProcessTimedOutException) {
        // the process is killed by then, and no shell stands in between that could outlive it
        return 'TIMEOUT';
    } catch (ProcessSignaledException) {
        // killed by a signal we did not send (OOM killer, segfault); our own timeout kill is the case above
        return 'CRASH';
    } finally {
        unlink($junit);
    }
}

/** @return ?string the problem with a case, null when it is fine */
function verdictOf(string $head, ?string $base, ?string $guard): ?string
{
    if ($head === 'SKIPPED') {
        return null;
    }

    return match (true) {
        $head !== 'PASS' => "$head on head: broken on head or wrong filter",
        $base === 'ERROR' => 'no tests executed on base: wrong filter',
        $base === 'SKIPPED' => 'skipped on base: cannot show it fails there',
        $base === 'PASS' && $guard === null
            => 'passes on base, asserts nothing: make it fail on base, or add a "// guard: <reason>" marker above it',
        $base !== 'PASS' && $guard !== null => 'stale guard marker: the case fails on base, remove the marker',
        default => null,
    };
}

$options = getopt('', ['base:', 'head:', 'timeout:', 'max-cases:', 'list']);
if (!is_string($options['base'] ?? null)) {
    fwrite(STDERR, "Usage: php $argv[0] --base=<rev> [--head=<rev>] [--timeout=120] [--max-cases=60] [--list]\n");
    exit(2);
}

$timeout = (float) ($options['timeout'] ?? 120);
$max_cases = (int) ($options['max-cases'] ?? 60);
$head = trim(must(['git', 'rev-parse', '--verify', ($options['head'] ?? 'HEAD') . '^{commit}']));
$merge_base = trim(must(['git', 'merge-base', $head, $options['base']]));

$head_files = [];
$base_files = [];
foreach (array_filter(explode("\n", must(['git', 'diff', '--name-status', '-M', $merge_base, $head, '--', 'tests/']))) as $line) {
    $columns = explode("\t", $line);
    $path = end($columns);
    if (!str_ends_with($path, 'Test.php') || str_starts_with($path, 'tests/fixtures/')) {
        continue;
    }

    // a deleted file is no case, but its entries may have moved to a changed file
    if ($columns[0] !== 'D') {
        $head_files[] = $path;
    }

    if ($columns[0] !== 'A') {
        $base_files[] = $columns[1];
    }
}

$known = [];
$known_values = [];
foreach ($base_files as $path) {
    foreach (analyse(show($merge_base, $path) ?? '<?php')['items'] as $item) {
        $known[$item['id']][$item['text']] = true;
        $known_values[$item['value']][$item['id']] = true;
    }
}

$analyses = [];
$head_ids = [];
foreach ($head_files as $path) {
    $analyses[$path] = analyse(show($head, $path) ?? '<?php');
    $head_ids += array_fill_keys(array_column($analyses[$path]['items'], 'id'), true);
}

$cases = [];
$parameterized = [];
foreach ($analyses as $path => $analysis) {
    foreach ($analysis['items'] as $item) {
        $same_id = $known[$item['id']] ?? null;
        // A new name with the value of a base item whose name is gone from the head is a rename. When the old name
        // is still there it is a copy (e.g. the old case rewritten, its previous version kept under a new name).
        if ($same_id !== null ? isset($same_id[$item['text']]) : array_diff_key($known_values[$item['value']] ?? [], $head_ids) !== []) {
            continue;
        }

        if ($item['parameterized']) {
            $parameterized[] = "$path:{$item['line']} {$item['name']}";
        } else {
            $cases[] = caseOf($item, $path, $analysis['types'], $head);
        }
    }
}

usort($cases, static fn(array $a, array $b): int => [$a['path'], $a['line']] <=> [$b['path'], $b['line']]);

foreach ($parameterized as $name) {
    echo "info: parameterized, not run (its provider entries are): $name\n";
}

if ($cases === []) {
    emit("no added or changed test cases\n");
    exit(0);
}

$selection = table(['case', 'file:line', 'guard', 'filter'], array_map(
    static fn(array $case): array => [$case['case'], "{$case['path']}:{$case['line']}", $case['guard'] ?? '-', "`{$case['regex']}`"],
    $cases,
));
if (array_key_exists('list', $options)) {
    echo $selection, count($cases) . " selected cases\n";
    exit(0);
}

if (count($cases) > $max_cases) {
    $message = count($cases) . " selected cases exceed --max-cases=$max_cases: split the PR or use a guard or skip label";
    emit("$selection$message\n");
    annotate($cases[0]['path'], $cases[0]['line'], $message);
    exit(1);
}

// the head, and the merge base with the head's tests
$worktrees = [];
register_shutdown_function(static function () use (&$worktrees): void {
    if (function_exists('pcntl_signal')) {
        // another interrupt must not abort the cleanup half way
        pcntl_signal(SIGINT, SIG_IGN);
        pcntl_signal(SIGTERM, SIG_IGN);
    }

    foreach ($worktrees as $dir) {
        (new Process(['git', 'worktree', 'remove', '--force', $dir], ROOT))->run();
    }

    (new Process(['git', 'worktree', 'prune'], ROOT))->run();
});
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    foreach ([SIGINT, SIGTERM] as $signal) {
        pcntl_signal($signal, static function () use ($signal, &$active_process): never {
            $active_process?->stop(0);
            throw new RuntimeException("Interrupted by signal $signal");
        });
    }
}

foreach (['head' => $head, 'base' => $merge_base] as $side => $rev) {
    $dir = sys_get_temp_dir() . '/psalm-tests-fail-on-base-' . getmypid() . "-$side";
    $worktrees[$side] = $dir;
    must(['git', 'worktree', 'add', '--detach', $dir, $rev]);
    if ($side === 'base') {
        must(['git', 'rm', '-rfq', '--', 'tests'], $dir);
        must(['git', 'checkout', $head, '--', 'tests'], $dir);
    }

    must(['cp', '-R', ROOT . '/vendor', "$dir/vendor"]);
    $composer_changed = show($rev, 'composer.json') !== file_get_contents(ROOT . '/composer.json');
    must(
        ['composer', $composer_changed ? 'update' : 'dump-autoload', '--quiet', '--no-interaction'],
        $dir,
        $composer_changed ? ['COMPOSER_ROOT_VERSION' => 'dev-master'] : [],
    );
}

$rows = [];
$failed = false;
foreach ($cases as $case) {
    $head_status = statusOf($worktrees['head'], $case['dir'], $case['regex'], $timeout);
    $base_status = $head_status === 'PASS' ? statusOf($worktrees['base'], $case['dir'], $case['regex'], $timeout) : null;
    $problem = verdictOf($head_status, $base_status, $case['guard']);
    fwrite(STDERR, "{$case['path']}:{$case['line']} {$case['case']}: head $head_status, base " . ($base_status ?? '-') . "\n");
    if ($problem !== null) {
        $failed = true;
        annotate($case['path'], $case['line'], "{$case['case']}: $problem");
    }

    $rows[] = [$case['case'], "{$case['path']}:{$case['line']}", $head_status, $base_status ?? '-', $case['guard'] ?? '-', $problem ?? 'ok'];
}

emit(table(['case', 'file:line', 'head', 'base', 'guard', 'verdict'], $rows));
exit($failed ? 1 : 0);
