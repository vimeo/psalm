<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser\Modifiers;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Issue\ParseError;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\PropertyStorage;

/**
 * Resolves the get/set visibility of a property declaration (including PHP 8.4
 * asymmetric visibility) and reports declaration-level errors.
 *
 * @internal
 */
final class PropertyVisibilityResolver
{
    /**
     * @param int $flags the php-parser modifier flags of the property or promoted parameter
     */
    public static function resolve(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        PropertyStorage $property_storage,
        int $flags,
        CodeLocation $location,
        string $property_id,
    ): void {
        switch ($flags & Modifiers::VISIBILITY_MASK) {
            case Modifiers::PROTECTED:
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
                break;

            case Modifiers::PRIVATE:
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
                break;

            default:
                $property_storage->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
        }

        $property_storage->is_final = $property_storage->is_final || (bool) ($flags & Modifiers::FINAL);

        $set_visibility = null;

        switch ($flags & Modifiers::VISIBILITY_SET_MASK) {
            case Modifiers::PUBLIC_SET:
                $set_visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
                break;

            case Modifiers::PROTECTED_SET:
                $set_visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
                break;

            case Modifiers::PRIVATE_SET:
                $set_visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
                break;
        }

        if ($set_visibility === null) {
            // Readonly properties are implicitly protected(set) as of PHP 8.4,
            // but the readonly-specific checks already cover them, so keep the
            // declared visibility to avoid double reporting.
            $property_storage->set_visibility = $property_storage->visibility;

            return;
        }

        if ($codebase->analysis_php_version_id < 8_04_00) {
            $class_storage->docblock_issues[] = new ParseError(
                'Asymmetric property visibility is only available in PHP 8.4 and later, but '
                    . $property_id . ' uses it',
                $location,
            );
        }

        if ($flags & Modifiers::STATIC) {
            $class_storage->docblock_issues[] = new ParseError(
                'Static property ' . $property_id . ' cannot have asymmetric visibility',
                $location,
            );
        }

        if ($property_storage->signature_type === null) {
            $class_storage->docblock_issues[] = new ParseError(
                'Property ' . $property_id . ' with asymmetric visibility must have a type',
                $location,
            );
        }

        if ($set_visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC && ($flags & Modifiers::READONLY)) {
            $class_storage->docblock_issues[] = new ParseError(
                'Readonly property ' . $property_id . ' cannot have public(set) visibility',
                $location,
            );
        }

        if ($set_visibility < $property_storage->visibility) {
            $class_storage->docblock_issues[] = new ParseError(
                'Visibility of property ' . $property_id . ' must not be weaker than its set visibility ('
                    . PropertyStorage::getVisibilityText($set_visibility) . '(set))',
                $location,
            );

            $set_visibility = $property_storage->visibility;
        }

        $property_storage->set_visibility = $set_visibility;

        if ($set_visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
            // private(set) properties are implicitly final
            $property_storage->is_final = true;
        }
    }
}
