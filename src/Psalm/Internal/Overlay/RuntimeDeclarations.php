<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Psalm\Config;

use function array_diff_key;
use function array_flip;
use function array_keys;
use function file_get_contents;
use function get_included_files;
use function ltrim;
use function strtolower;

/**
 * The functions and constants of an overlaid file that the process defines already: Composer loaded the file as it is
 * on disk (`autoload.files`). What the overlay no longer declares must not be taken from the process.
 *
 * @internal
 */
final class RuntimeDeclarations
{
    /**
     * @param array<string, string> $overlays path => contents
     */
    public static function forgetRemoved(Config $config, array $overlays): void
    {
        // the files Composer's autoload.files loaded; other project files declare nothing in this process
        $loaded = array_flip(get_included_files());

        foreach ($overlays as $path => $contents) {
            $original = isset($loaded[$path]) ? @file_get_contents($path) : false;
            if ($original === false) {
                continue;
            }

            [$original_functions, $original_constants] = self::getDeclarations($original);
            [$functions, $constants] = self::getDeclarations($contents);

            $config->forgetPredefined(
                array_keys(array_diff_key($original_functions, $functions)),
                array_keys(array_diff_key($original_constants, $constants)),
            );
        }
    }

    /**
     * The functions (lowercase ids) and constants a file declares, wherever in it
     *
     * @return array{array<string, true>, array<string, true>}
     */
    private static function getDeclarations(string $contents): array
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse($contents, new Collecting()) ?? [];
        $statements = (new NodeTraverser(new NameResolver()))->traverse($statements);
        $finder = new NodeFinder();

        $functions = [];
        foreach ($finder->findInstanceOf($statements, Function_::class) as $function) {
            if ($function->namespacedName !== null) {
                $functions[$function->namespacedName->toLowerString()] = true;
            }
        }

        $constants = [];
        foreach ($finder->findInstanceOf($statements, Const_::class) as $statement) {
            foreach ($statement->consts as $constant) {
                if ($constant->namespacedName !== null) {
                    $constants[$constant->namespacedName->toString()] = true;
                }
            }
        }

        foreach ($finder->findInstanceOf($statements, FuncCall::class) as $call) {
            if ($call->isFirstClassCallable()) {
                continue;
            }

            $argument = $call->getArgs()[0] ?? null;
            if ($call->name instanceof Name
                && strtolower($call->name->getLast()) === 'define'
                && $argument !== null
                && $argument->value instanceof String_
            ) {
                $constants[ltrim($argument->value->value, '\\')] = true;
            }
        }

        return [$functions, $constants];
    }
}
