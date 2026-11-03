<?php

/**
 * Checks the changes applied to the working tree from the patch of the "Build docker image for stubs" workflow before
 * it is pushed: the patch comes from a job that runs the dependencies' code, so it may only modify the files composer
 * build generates, and the PHP ones may only hold data, no code that could run when Psalm loads them.
 *
 * Usage: php bin/ci/check-composer-build-patch.php (from the repository root, after git apply)
 */

declare(strict_types=1);

const ALLOWED_PATHS = [
    '~^dictionaries/(autogen/|override/)?CallMap(_[0-9]+(_delta)?)?\.php$~',
    '~^src/Psalm/Internal/PreloaderList\.php$~',
    '~^docs/running_psalm/(error_levels|issues)\.md$~',
];

// arrays, scalars, class constants and ::class names; a ( may only follow array or declare, so nothing can be called
const ALLOWED_TOKENS = [
    T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_DECLARE, T_NAMESPACE, T_RETURN, T_ARRAY,
    T_FINAL, T_CLASS, T_PUBLIC, T_CONST, T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_DOUBLE_COLON,
    T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DOUBLE_ARROW,
    '[', ']', '(', ')', ',', ';', '=', '{', '}',
];

/** @param list<string> $command */
$run = static function (array $command): string {
    exec(implode(' ', array_map('escapeshellarg', $command)) . ' 2>&1', $output, $code);
    if ($code !== 0) {
        throw new RuntimeException(implode(' ', $command) . " failed:\n" . implode("\n", $output));
    }

    return implode("\n", $output);
};

$errors = [];

if ($run(['git', 'diff', '--summary']) !== '') {
    $errors[] = 'The patch changes file modes, or creates or deletes files';
}

$status_output = rtrim($run(['git', 'status', '--porcelain=v1', '-z', '--untracked-files=all']), "\0");
foreach ($status_output === '' ? [] : explode("\0", $status_output) as $entry) {
    $status = substr($entry, 0, 2);
    $path = substr($entry, 3);
    if ($status !== ' M') {
        $errors[] = "$path: not a modified file ($status)";
        continue;
    }

    if (!array_filter(ALLOWED_PATHS, static fn(string $pattern): bool => preg_match($pattern, $path) === 1)) {
        $errors[] = "$path: composer build doesn't generate it";
        continue;
    }

    if (!str_ends_with($path, '.php')) {
        continue;
    }

    $previous = null;
    foreach (token_get_all((string) file_get_contents($path)) as $token) {
        $type = is_array($token) ? $token[0] : $token;
        if (!in_array($type, ALLOWED_TOKENS, true)
            || ($type === '(' && $previous !== T_ARRAY && $previous !== T_DECLARE)
        ) {
            $line = is_array($token) ? " on line $token[2]" : '';
            $errors[] = "$path: unexpected " . (is_int($type) ? token_name($type) : "'$type'") . $line;
            continue 2;
        }

        if ($type !== T_WHITESPACE && $type !== T_COMMENT && $type !== T_DOC_COMMENT) {
            $previous = $type;
        }
    }

    try {
        $run([PHP_BINARY, '-l', $path]);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}

foreach ($errors as $error) {
    echo "::error::$error" . PHP_EOL;
}

exit($errors === [] ? 0 : 1);
