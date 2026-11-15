<?php

declare(strict_types=1);

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
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;
use UnexpectedValueException;

use function strtolower;

/**
 * @internal
 */
final class TypeHintResolver
{
    /**
     * The keys by which a member of a native union duplicates another one, each with the name PHP reports. PHP never
     * resolves `static`, and resolves `self` and `parent` to the class name only from PHP 8.5 on, in a class that is
     * neither a trait nor anonymous.
     *
     * @return array<string, string>
     */
    private static function getDuplicateKeysOfAUnionMember(
        PhpParser\NodeAbstract $hint,
        Union $resolved_type,
        ?ClassLikeStorage $classlike_storage,
        int $analysis_php_version_id,
    ): array {
        if ($hint instanceof PhpParser\Node\Name && !$hint instanceof PhpParser\Node\Name\FullyQualified) {
            $lower_hint = strtolower($hint->getFirst());

            if ($lower_hint === 'static') {
                return ['static' => 'static'];
            }

            if ($lower_hint === 'self' || $lower_hint === 'parent') {
                $class_name = $classlike_storage
                    && $analysis_php_version_id >= 8_05_00
                    && !$classlike_storage->is_trait
                    && !$classlike_storage->anonymous
                    ? ($lower_hint === 'self' ? $classlike_storage->name : $classlike_storage->parent_class)
                    : null;

                return $class_name !== null ? [$class_name => $class_name] : [$lower_hint => $lower_hint];
            }
        }

        $keys = [];

        foreach ($resolved_type->getAtomicTypes() as $key => $_) {
            // the key of a builtin type can carry more than its name (`callable[impure]`)
            $keys[$key] = $hint instanceof Identifier ? strtolower($hint->name) : $key;
        }

        return $keys;
    }

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
        int $analysis_php_version_id,
    ): Union {
        if ($hint instanceof PhpParser\Node\UnionType) {
            $type = null;

            if (!$hint->types) {
                throw new UnexpectedValueException('Union type should not be empty');
            }

            if ($analysis_php_version_id < 8_00_00) {
                $file_storage->scan_issues[] = new ParseError(
                    'Union types are not supported in PHP < 8',
                    $code_location,
                );
            }

            $seen_keys = [];

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

                $member_keys = self::getDuplicateKeysOfAUnionMember(
                    $atomic_typehint,
                    $resolved_type,
                    $classlike_storage,
                    $analysis_php_version_id,
                );

                foreach ($member_keys as $member_key => $member_name) {
                    if (isset($seen_keys[$member_key])) {
                        $file_storage->scan_issues[] = new ParseError(
                            'Duplicate type ' . $member_name . ' is redundant',
                            $code_location,
                        );
                    }
                }

                $seen_keys += $member_keys;

                $type = Type::combineUnionTypes($resolved_type, $type);
            }

            return $type;
        }

        if ($hint instanceof PhpParser\Node\IntersectionType) {
            if (!$hint->types) {
                throw new UnexpectedValueException('Intersection type should not be empty');
            }

            if ($analysis_php_version_id < 8_01_00) {
                $file_storage->scan_issues[] = new ParseError(
                    'Intersection types are not supported in PHP < 8.1',
                    $code_location,
                );
            }

            // Build the intersection structurally, like the docblock parser does. This runs while scanning, so
            // class storage for the members may not be populated yet (and in a parallel scan whether it is depends
            // on which files a worker happened to scan first). Any narrowing that needs class hierarchy data
            // belongs to the analysis phase.
            $first_type = null;
            $extra_types = [];

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

                foreach ($resolved_type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type instanceof TNamedObject) {
                        $file_storage->scan_issues[] = new ParseError(
                            $resolved_type->hasScalarType()
                                ? 'Intersection types cannot contain scalar types'
                                : 'Intersection types can only contain class types',
                            $code_location,
                        );

                        return Type::getMixed();
                    }

                    if ($first_type === null) {
                        $first_type = $atomic_type;
                    } else {
                        $extra_types[$atomic_type->getKey()] = $atomic_type;
                    }
                }
            }

            if ($extra_types) {
                $first_type = $first_type->setIntersectionTypes([...$first_type->extra_types, ...$extra_types]);
            }

            return new Union([$first_type]);
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
            $lower_hint = strtolower($hint->getFirst());

            if ($classlike_storage
                && ($lower_hint === 'self' || $lower_hint === 'static')
                && !$classlike_storage->is_trait
            ) {
                $fq_type_string = $classlike_storage->name;

                if ($lower_hint === 'static') {
                    $fq_type_string .= '&static';
                }
            } else {
                $type_string = $hint->toString();
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
