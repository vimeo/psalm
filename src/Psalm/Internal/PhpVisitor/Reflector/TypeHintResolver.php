<?php

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Issue\ParseError;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;
use UnexpectedValueException;

use function implode;
use function strtolower;

/**
 * @internal
 */
class TypeHintResolver
{
    /**
     * @param Identifier|IntersectionType|Name|NullableType|UnionType $hint
     */
    public static function resolve(
        PhpParser\NodeAbstract $hint,
        CodeLocation $code_location,
        Codebase $codebase,
        FileStorage $file_storage,
        ?ClassLikeStorage $classlike_storage,
        Aliases $aliases,
        int $analysis_php_version_id
    ): Union {
        if ($hint instanceof PhpParser\Node\UnionType) {
            $type = null;

            if (!$hint->types) {
                throw new UnexpectedValueException('Union type should not be empty');
            }

            if ($analysis_php_version_id < 8_00_00) {
                IssueBuffer::maybeAdd(
                    new ParseError(
                        'Union types are not supported in PHP < 8',
                        $code_location,
                    ),
                );
            }

            foreach ($hint->types as $atomic_typehint) {
                $resolved_type = self::resolve(
                    $atomic_typehint,
                    $code_location,
                    $codebase,
                    $file_storage,
                    $classlike_storage,
                    $aliases,
                    $analysis_php_version_id,
                );

                $type = Type::combineUnionTypes($resolved_type, $type);
            }

            return $type;
        }

        if ($hint instanceof PhpParser\Node\IntersectionType) {
            $type = null;

            if (!$hint->types) {
                throw new UnexpectedValueException('Intersection type should not be empty');
            }

            if ($analysis_php_version_id < 8_01_00) {
                IssueBuffer::maybeAdd(
                    new ParseError(
                        'Intersection types are not supported in PHP < 8.1',
                        $code_location,
                    ),
                );
            }

            foreach ($hint->types as $atomic_typehint) {
                $resolved_type = self::resolve(
                    $atomic_typehint,
                    $code_location,
                    $codebase,
                    $file_storage,
                    $classlike_storage,
                    $aliases,
                    $analysis_php_version_id,
                );

                if ($resolved_type->hasScalarType()) {
                    IssueBuffer::maybeAdd(
                        new ParseError(
                            'Intersection types cannot contain scalar types',
                            $code_location,
                        ),
                    );
                }

                $type = Type::intersectUnionTypes($resolved_type, $type, $codebase);
            }

            if ($type === null) {
                $type = Type::getNever();
            }

            return $type;
        }

        $is_nullable = false;

        if ($hint instanceof PhpParser\Node\NullableType) {
            $is_nullable = true;
            $hint = $hint->type;
        }

        $type_string = null;

        if ($hint instanceof PhpParser\Node\Identifier) {
            $fq_type_string = $hint->name;
        } elseif ($hint instanceof PhpParser\Node\Name\FullyQualified) {
            $fq_type_string = (string)$hint;

            $codebase->scanner->queueClassLikeForScanning($fq_type_string);
            $file_storage->referenced_classlikes[strtolower($fq_type_string)] = $fq_type_string;
        } else {
            $lower_hint = strtolower($hint->parts[0]);

            if ($classlike_storage
                && ($lower_hint === 'self' || $lower_hint === 'static')
                && !$classlike_storage->is_trait
            ) {
                $fq_type_string = $classlike_storage->name;

                if ($lower_hint === 'static') {
                    $fq_type_string .= '&static';
                }
            } else {
                $type_string = implode('\\', $hint->parts);
                $fq_type_string = ClassLikeAnalyzer::getFQCLNFromNameObject($hint, $aliases);

                $codebase->scanner->queueClassLikeForScanning($fq_type_string);
                $file_storage->referenced_classlikes[strtolower($fq_type_string)] = $fq_type_string;
            }
        }

        $type = Type::parseString(
            $fq_type_string,
            $analysis_php_version_id,
            [],
        );

        if ($type_string) {
            $atomic_type = $type->getSingleAtomic();
            /** @psalm-suppress InaccessibleProperty We just created this type */
            $atomic_type->text = $type_string;
        }

        if ($is_nullable) {
            $type = $type->getBuilder()->addType(new TNull)->freeze();
        }

        return $type;
    }
}
