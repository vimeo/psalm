<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use Psalm\Aliases;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Storage\Capabilities;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Union;

use function preg_replace;
use function trim;

/**
 * Resolves the value of a `@psalm-capabilities` tag that is not a plain list of capability
 * names: a purity type, which may use the type aliases in scope (`@psalm-type`,
 * `@psalm-import-type`), the way Hack's context constants name a set of capabilities once.
 *
 *     /** @psalm-type Storage = write-props|io *\/
 *     final class Repo {
 *         /** @psalm-capabilities Storage *\/
 *         public function save(): void {}
 *     }
 *
 * @internal
 */
final class CapabilitiesExpressionResolver
{
    /**
     * @param array<string, non-empty-array<string, Union>> $template_types
     * @param array<string, TypeAlias> $type_aliases
     * @return int|Union the capabilities, or the purity type when it names imported type aliases
     *                   that can only be resolved once every class is scanned
     * @throws TypeParseTreeException
     */
    public static function resolve(
        string $expression,
        Aliases $aliases,
        array $template_types,
        array $type_aliases,
        ?string $self_fqcln = null,
    ): int|Union {
        // the list separators are all unions of capabilities
        $type_string = (string) preg_replace('/[\s,]+/', '|', trim($expression));
        $type_string = trim($type_string, '|');

        $type = TypeParser::parseTokens(
            TypeTokenizer::getFullyQualifiedTokens(
                $type_string,
                $aliases,
                $template_types,
                $type_aliases,
                $self_fqcln,
            ),
            null,
            $template_types,
            $type_aliases,
        );

        if (!Capabilities::isPurityType($type)) {
            throw new TypeParseTreeException(
                '@psalm-capabilities ' . $expression . ' does not denote a set of capabilities',
            );
        }

        if ($type->hasTypeAlias()) {
            return $type;
        }

        return Capabilities::fromType($type);
    }

    /**
     * The purity type holding both the capabilities already known and the aliases still to be
     * resolved, for the populator.
     *
     * @param list<Union> $deferred
     */
    public static function deferred(int $capabilities, array $deferred): Union
    {
        $atomics = [new TCapabilities($capabilities)];

        foreach ($deferred as $type) {
            foreach ($type->getAtomicTypes() as $atomic) {
                $atomics[] = $atomic;
            }
        }

        return new Union($atomics);
    }
}
