<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InternalProperty;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\Issue\UndefinedMagicPropertyAssignment;
use Psalm\Issue\UndefinedMagicPropertyFetch;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TNamedObject;

use function count;
use function explode;
use function strtolower;

/**
 * What reading and writing a property have in common: resolving it through `__get()`/`__set()`, and checking the
 * property once it is found or reporting that it isn't
 *
 * @internal
 */
final class PropertyAccessAnalyzer
{
    /**
     * Whether the property isn't visible from here for the access, outside the class and `$this`
     */
    public static function isInaccessibleFromOutside(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        Context $context,
        string $property_id,
        string $fq_class_name,
        ?string $lhs_var_id,
        bool $is_write,
    ): bool {
        return $lhs_var_id !== '$this'
            && $fq_class_name !== $context->self
            && ClassLikeAnalyzer::checkPropertyVisibility(
                $property_id,
                $context,
                $statements_analyzer,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues(),
                false,
                $is_write,
            ) !== true;
    }

    /**
     * Whether the property is one of the current class that is reached on an object of a child class, where a private
     * property of the current class is still the one accessed
     */
    public static function isPropertyOfSelfOnChild(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        Context $context,
        string $fq_class_name,
        string $prop_name,
        bool $is_write,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        return $context->self !== null
            && $fq_class_name !== $context->self
            && $codebase->classlikes->classExtends($fq_class_name, $context->self)
            && $codebase->propertyExists(
                $context->self . '::$' . $prop_name,
                !$is_write,
                $statements_analyzer,
                $context,
                !$is_write && $codebase->collect_locations
                    ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                    : null,
            );
    }

    /**
     * Whether the class has the `__get()` or `__set()` that an access to a missing or inaccessible property calls
     */
    public static function magicMethodExists(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        Context $context,
        MethodIdentifier $magic_method_id,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        return $codebase->methodExists(
            $magic_method_id,
            $context->calling_method_id,
            $codebase->collect_locations
                ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                : null,
            !$context->collect_initializations
                ? $statements_analyzer
                : null,
            $statements_analyzer->getFilePath(),
        );
    }

    /**
     * Queues the rename of an instance property (Psalter `--move`)
     */
    public static function handleRename(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        string $declaring_property_class,
        string $prop_name,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->properties_to_rename) {
            return;
        }

        $new_property_name = $codebase->properties_to_rename[strtolower($declaring_property_class) . '::$' . $prop_name]
            ?? null;

        if ($new_property_name !== null) {
            FileManipulationBuffer::add(
                $statements_analyzer->getFilePath(),
                [
                    new FileManipulation(
                        (int) $stmt->name->getAttribute('startFilePos'),
                        (int) $stmt->name->getAttribute('endFilePos') + 1,
                        $new_property_name,
                    ),
                ],
            );
        }
    }

    /**
     * Checks a declared property that is read or written: internal, or newer than the analysed PHP version
     *
     * @param string $reported_property_id the property as the issues name it
     */
    public static function checkDeclaredProperty(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch|StaticPropertyFetch $stmt,
        Context $context,
        string $prop_name,
        string $reported_property_id,
        ClassLikeStorage $declaring_class_storage,
        bool $is_write,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $property_storage = $declaring_class_storage->properties[$prop_name] ?? null;

        if ($property_storage === null) {
            return;
        }

        // A property inherits the availability of its (native) declaring class unless it
        // carries a later `@since` of its own, which then takes priority. Reported when the
        // owning value came from e.g. a native factory return, so the class is never named in
        // the analysed code and would otherwise go unchecked.
        $property_since_id = $property_storage->since_php_version_id
            ?? $declaring_class_storage->since_php_version_id;

        if (!$declaring_class_storage->user_defined
            && $property_since_id !== null
            && ($codebase->getGuardedPhpVersionId($context) ?? $codebase->analysis_php_version_id)
                < $property_since_id
            && !$codebase->isClassLikePolyfilled($declaring_class_storage->name)
        ) {
            $message = $reported_property_id . ' ' . $codebase->getUnavailableSymbolMessageSuffix($property_since_id);
            $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

            IssueBuffer::maybeAdd(
                $is_write
                    ? new UndefinedPropertyAssignment($message, $code_location, $reported_property_id)
                    : new UndefinedPropertyFetch($message, $code_location, $reported_property_id),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $caller_identifier = $statements_analyzer->getFullyQualifiedFunctionMethodOrNamespaceName() ?? '';

        if (!NamespaceAnalyzer::isWithinAny($caller_identifier, $property_storage->internal)) {
            IssueBuffer::maybeAdd(
                new InternalProperty(
                    $reported_property_id
                        . ' is internal to ' . InternalClass::listToPhrase($property_storage->internal)
                        . ' but called from ' . ($caller_identifier ?: 'root namespace'),
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $reported_property_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    /**
     * Reports a deprecated property by its declaring class, whichever class it is reached through
     */
    public static function checkDeprecation(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch|StaticPropertyFetch $stmt,
        string $prop_name,
        ClassLikeStorage $declaring_class_storage,
    ): void {
        $declaring_property_id = $declaring_class_storage->name . '::$' . $prop_name;

        if (isset($declaring_class_storage->properties[$prop_name])
            && $declaring_class_storage->properties[$prop_name]->deprecated
        ) {
            $message = $declaring_property_id . ' is marked deprecated';
        } elseif (!$declaring_class_storage->user_defined) {
            $message = PhpDeprecations::getPropertyMessage($declaring_property_id, $declaring_property_id);
        } else {
            $message = null;
        }

        if ($message !== null) {
            IssueBuffer::maybeAdd(
                new DeprecatedProperty(
                    $message,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $declaring_property_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    /**
     * @param bool $is_static_property whether the property is static, as it is reached
     */
    public static function reportUndefinedProperty(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch|StaticPropertyFetch $stmt,
        string $property_id,
        bool $is_write,
        bool $on_this = false,
        bool $through_magic_method = false,
        bool $is_static_property = false,
    ): void {
        $kind = $is_static_property ? 'Static' : ($through_magic_method && !$on_this ? 'Magic instance' : 'Instance');
        $message = $kind . ' property ' . $property_id . ' is not defined';

        if (!$is_static_property && !$through_magic_method) {
            $message .= self::getPseudoPropertyHint($statements_analyzer->getCodebase(), $property_id, $is_write);
        }

        $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        if ($on_this) {
            $issue = $is_write
                ? new UndefinedThisPropertyAssignment($message, $code_location, $property_id)
                : new UndefinedThisPropertyFetch($message, $code_location, $property_id);
        } elseif ($through_magic_method) {
            $issue = $is_write
                ? new UndefinedMagicPropertyAssignment($message, $code_location, $property_id)
                : new UndefinedMagicPropertyFetch($message, $code_location, $property_id);
        } else {
            $issue = $is_write
                ? new UndefinedPropertyAssignment($message, $code_location, $property_id)
                : new UndefinedPropertyFetch($message, $code_location, $property_id);
        }

        IssueBuffer::maybeAdd($issue, $statements_analyzer->getSuppressedIssues());
    }

    /**
     * A property a docblock of its class declares is only read through `__get()` (written through `__set()`) unless
     * the config says otherwise: the message of an undefined one says so
     *
     * @psalm-capabilities read-props
     */
    private static function getPseudoPropertyHint(Codebase $codebase, string $property_id, bool $is_write): string
    {
        $parts = explode('::$', $property_id, 2);

        if (count($parts) !== 2 || !$codebase->classlike_storage_provider->has($parts[0])) {
            return '';
        }

        $class_storage = $codebase->classlike_storage_provider->get($parts[0]);
        $pseudo_property_types = $is_write
            ? $class_storage->pseudo_property_set_types
            : $class_storage->pseudo_property_get_types;

        if (!isset($pseudo_property_types['$' . $parts[1]])) {
            return '';
        }

        return ', but a docblock of ' . $class_storage->name . ' declares it: add '
            . ($is_write ? '__set()' : '__get()') . ' to ' . $class_storage->name
            . ', or set usePhpDocPropertiesWithoutMagicCall="true" in the config';
    }

    public static function reportUndefinedClass(
        StatementsAnalyzer $statements_analyzer,
        PropertyFetch $stmt,
        TNamedObject $lhs_type_part,
        bool $is_write,
    ): void {
        $message = 'Cannot ' . ($is_write ? 'set' : 'get') . ' properties of undefined '
            . ($lhs_type_part->from_docblock ? 'docblock ' : '') . 'class ' . $lhs_type_part->value;
        $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        IssueBuffer::maybeAdd(
            $lhs_type_part->from_docblock
                ? new UndefinedDocblockClass($message, $code_location, $lhs_type_part->value)
                : new UndefinedClass($message, $code_location, $lhs_type_part->value),
            $statements_analyzer->getSuppressedIssues(),
        );
    }
}
