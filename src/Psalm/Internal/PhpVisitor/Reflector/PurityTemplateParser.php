<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use Psalm\Exception\IncorrectDocblockException;

use function array_shift;
use function array_slice;
use function preg_split;
use function strtolower;
use function substr;
use function trim;

/**
 * Parses the value of a `@psalm-purity-template` tag, e.g. `P`, `P, Q`, `P of write-props|io`
 * or `C of write-props = pure`: `of` gives the upper bound (the most a value of the template
 * may require, `impure` when omitted) and `=` the default of a class template, for the
 * subclasses that do not bind it.
 *
 * @internal
 */
final class PurityTemplateParser
{
    /**
     * @return list<array{name: string, bound: string, default: ?string}>
     * @throws IncorrectDocblockException
     * @psalm-pure
     */
    public static function parse(string $line): array
    {
        $tokens = [];

        foreach (preg_split('/\s+/', trim($line)) ?: [] as $token) {
            if ($token === '') {
                continue;
            }

            // `P, Q` and `P,Q`
            foreach (preg_split('/,/', $token) ?: [] as $part) {
                if ($part !== '') {
                    $tokens[] = $part;
                }
            }
        }

        if ($tokens === []) {
            throw new IncorrectDocblockException('Empty @psalm-purity-template tag');
        }

        $templates = [];

        while ($tokens !== []) {
            $name = array_shift($tokens);
            $bound = 'impure';
            $default = null;

            if (strtolower($name) === 'of' || $name === '=') {
                throw new IncorrectDocblockException(
                    '@psalm-purity-template expects a template name before ' . $name,
                );
            }

            if (isset($tokens[0], $tokens[1]) && strtolower($tokens[0]) === 'of') {
                $bound = $tokens[1];
                $tokens = array_slice($tokens, 2);
            }

            if (isset($tokens[0], $tokens[1]) && $tokens[0] === '=') {
                $default = $tokens[1];
                $tokens = array_slice($tokens, 2);
            } elseif (isset($tokens[0]) && $tokens[0][0] === '=') {
                // `= pure` written as `=pure`
                $default = substr($tokens[0], 1);
                $tokens = array_slice($tokens, 1);
            }

            if (isset($tokens[0]) && (strtolower($tokens[0]) === 'of' || $tokens[0] === '=')) {
                throw new IncorrectDocblockException(
                    '@psalm-purity-template ' . $name . ' has an incomplete bound or default',
                );
            }

            $templates[] = ['name' => $name, 'bound' => $bound, 'default' => $default];
        }

        return $templates;
    }
}
