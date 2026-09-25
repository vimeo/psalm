<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use Psalm\Exception\IncorrectDocblockException;
use Psalm\Storage\Capabilities;

use function count;
use function explode;
use function preg_match;
use function strtolower;
use function trim;

/**
 * Parses the value of a `@psalm-purity-template` tag: a comma-separated list of templates, each
 * written as a chain `lower <= Name(default) <= upper` where every part but the name is optional,
 * e.g. `P`, `P, Q`, `P <= write-props|io`, `write-this-props <= C(write-this-props) <= io`.
 * The upper bound is the most a value of the template may require (`impure` when omitted), the
 * lower bound of a class template the least every value requires (which the methods depending on
 * the template may use), and the default of a class template what the subclasses that do not
 * bind it get.
 *
 * @internal
 */
final class PurityTemplateParser
{
    private const NAME_PATTERN = '/^([A-Za-z_][A-Za-z0-9_]*)\s*(?:\(([^()]*)\))?$/';

    private const SYNTAX = '`lower <= Name(default) <= upper`';

    /**
     * @return list<array{name: string, bound: string, lower: ?string, default: ?string}>
     * @throws IncorrectDocblockException
     * @psalm-pure
     */
    public static function parse(string $line): array
    {
        if (trim($line) === '') {
            throw new IncorrectDocblockException('Empty @psalm-purity-template tag');
        }

        $templates = [];

        foreach (explode(',', $line) as $entry) {
            $parts = explode('<=', $entry);

            foreach ($parts as $i => $part) {
                $parts[$i] = trim($part);

                if ($parts[$i] === '') {
                    throw self::invalid($entry);
                }
            }

            if (count($parts) === 1) {
                [$lower, $name, $upper] = [null, $parts[0], null];
            } elseif (count($parts) === 2) {
                // `P <= io` or `write-props <= C`: the name is the side that is not a capability
                [$lower, $name, $upper] = self::isTemplate($parts[0]) || !self::isTemplate($parts[1])
                    ? [null, $parts[0], $parts[1]]
                    : [$parts[0], $parts[1], null];
            } elseif (count($parts) === 3) {
                [$lower, $name, $upper] = $parts;
            } else {
                throw self::invalid($entry);
            }

            if (!preg_match(self::NAME_PATTERN, $name, $matches)) {
                throw self::invalid($entry);
            }

            $default = isset($matches[2]) ? trim($matches[2]) : null;

            if ($default === '') {
                throw self::invalid($entry);
            }

            $templates[] = [
                'name' => $matches[1],
                'bound' => $upper ?? 'impure',
                'lower' => $lower,
                'default' => $default,
            ];
        }

        return $templates;
    }

    /**
     * Whether one side of a two-part chain is the template rather than a bound: it has a default,
     * or it is a single name that is not a capability. A type alias used as the lower bound
     * therefore needs the upper bound written too (`Alias <= C <= impure`).
     *
     * @psalm-pure
     */
    private static function isTemplate(string $part): bool
    {
        return preg_match(self::NAME_PATTERN, $part, $matches) === 1
            && (isset($matches[2]) || !isset(Capabilities::NAMES[strtolower($matches[1])]));
    }

    /** @psalm-pure */
    private static function invalid(string $entry): IncorrectDocblockException
    {
        return new IncorrectDocblockException(
            '@psalm-purity-template expects ' . self::SYNTAX . ', got `' . trim($entry) . '`',
        );
    }
}
