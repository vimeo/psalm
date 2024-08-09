<?php

namespace Psalm\Internal;

use PhpParser;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\PhpVersion;

use function class_exists;

/**
 * @internal
 */
final class BCHelper
{
    private const CLASS_MAP = [
        Node\Expr\ArrayItem::class => Node\ArrayItem::class,
        Node\Expr\ClosureUse::class => Node\ClosureUse::class,
        Node\Scalar\DNumber::class => Node\Scalar\Float_::class,
        Node\Scalar\Encapsed::class => Node\Scalar\InterpolatedString::class,
        Node\Scalar\EncapsedStringPart::class => Node\InterpolatedStringPart::class,
        Node\Scalar\LNumber::class => Node\Scalar\Int_::class,
        Node\Stmt\DeclareDeclare::class => Node\DeclareItem::class,
        Node\Stmt\PropertyProperty::class => Node\PropertyItem::class,
        Node\Stmt\StaticVar::class => Node\StaticVar::class,
        Node\Stmt\UseUse::class => Node\UseItem::class,
    ];

    public static function getPHPParserClassName(string $className): string
    {
        if (isset(self::CLASS_MAP[$className]) && class_exists(self::CLASS_MAP[$className])) {
            return self::CLASS_MAP[$className];
        }

        return $className;
    }

    public static function usePHPParserV4(): bool
    {
        return class_exists('\PhpParser\Node\Stmt\Throw');
    }

    public static function isThrow(Node $stmt): bool
    {
        if (self::usePHPParserV4()) {
            return $stmt instanceof PhpParser\Node\Stmt\Throw_;
        }

        return $stmt instanceof PhpParser\Node\Stmt\Expression
            && $stmt->expr instanceof PhpParser\Node\Expr\Throw_;
    }

    public static function isThrowStatement(Node $node): bool
    {
        if (self::usePHPParserV4()) {
            return $node instanceof PhpParser\Node\Stmt\Throw_;
        }

        return false;
    }

    public static function createEmulative(int $major_version, int $minor_version): PhpParser\Lexer\Emulative
    {
        if (class_exists(PhpVersion::class)) {
            return new Emulative(PhpVersion::fromComponents($major_version, $minor_version));
        }

        return new Emulative([
            'usedAttributes' => [
                'comments', 'startLine', 'startFilePos', 'endFilePos',
            ],
            'phpVersion' => $major_version . '.' . $minor_version,
        ]);
    }
}
