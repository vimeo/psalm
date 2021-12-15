<?php

namespace Psalm\Internal\Analyzer;

use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\Issue\InvalidAttribute;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualNew;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\Stmt\VirtualExpression;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Storage\AttributeStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;

use function reset;

class AttributeAnalyzer
{
    /**
     * @param  array<string>    $suppressed_issues
     * @param  1|2|4|8|16|32 $target
     */
    public static function analyze(
        SourceAnalyzer $source,
        AttributeStorage $attribute,
        array $suppressed_issues,
        int $target,
        ?ClassLikeStorage $classlike_storage = null
    ): void {
        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $source,
            $attribute->fq_class_name,
            $attribute->location,
            null,
            null,
            $suppressed_issues,
            new ClassLikeNameOptions(
                false,
                false,
                false,
                false,
                false,
                true
            )
        ) === false) {
            return;
        }

        $codebase = $source->getCodebase();

        if (!$codebase->classlikes->classExists($attribute->fq_class_name)) {
            return;
        }

        if ($attribute->fq_class_name === 'Attribute' && $classlike_storage) {
            if ($classlike_storage->is_trait) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Traits cannot act as attribute classes',
                        $attribute->name_location
                    ),
                    $source->getSuppressedIssues()
                );
            } elseif ($classlike_storage->is_interface) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Interfaces cannot act as attribute classes',
                        $attribute->name_location
                    ),
                    $source->getSuppressedIssues()
                );
            } elseif ($classlike_storage->abstract) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Abstract classes cannot act as attribute classes',
                        $attribute->name_location
                    ),
                    $source->getSuppressedIssues()
                );
            } elseif (isset($classlike_storage->methods['__construct'])
                && $classlike_storage->methods['__construct']->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Classes with protected/private constructors cannot act as attribute classes',
                        $attribute->name_location
                    ),
                    $source->getSuppressedIssues()
                );
            } elseif ($classlike_storage->is_enum) {
                IssueBuffer::maybeAdd(
                    new InvalidAttribute(
                        'Enums cannot act as attribute classes',
                        $attribute->name_location
                    ),
                    $source->getSuppressedIssues()
                );
            }
        }

        self::checkAttributeTargets($source, $attribute, $target);

        $node_args = [];

        foreach ($attribute->args as $storage_arg) {
            $type = $storage_arg->type;

            if ($type instanceof UnresolvedConstantComponent) {
                $type = new Union([
                    ConstantTypeResolver::resolve(
                        $codebase->classlikes,
                        $type,
                        $source instanceof StatementsAnalyzer ? $source : null
                    )
                ]);
            }

            if ($type->isMixed()) {
                return;
            }

            $type_expr = StubsGenerator::getExpressionFromType(
                $type
            );

            $arg_attributes = [
                'startFilePos' => $storage_arg->location->raw_file_start,
                'endFilePos' => $storage_arg->location->raw_file_end,
                'startLine' => $storage_arg->location->raw_line_number
            ];

            $type_expr->setAttributes($arg_attributes);

            $node_args[] = new VirtualArg(
                $type_expr,
                false,
                false,
                $arg_attributes,
                $storage_arg->name
                    ? new VirtualIdentifier(
                        $storage_arg->name,
                        $arg_attributes
                    )
                    : null
            );
        }

        $new_stmt = new VirtualNew(
            new VirtualFullyQualified(
                $attribute->fq_class_name,
                [
                    'startFilePos' => $attribute->name_location->raw_file_start,
                    'endFilePos' => $attribute->name_location->raw_file_end,
                    'startLine' => $attribute->name_location->raw_line_number
                ]
            ),
            $node_args,
            [
                'startFilePos' => $attribute->location->raw_file_start,
                'endFilePos' => $attribute->location->raw_file_end,
                'startLine' => $attribute->location->raw_line_number
            ]
        );

        $statements_analyzer = new StatementsAnalyzer(
            $source,
            new NodeDataProvider()
        );

        $statements_analyzer->analyze(
            [new VirtualExpression($new_stmt)],
            new Context()
        );
    }

    /**
     * @param  1|2|4|8|16|32 $target
     */
    private static function checkAttributeTargets(
        SourceAnalyzer $source,
        AttributeStorage $attribute,
        int $target
    ): void {
        $codebase = $source->getCodebase();

        $attribute_class_storage = $codebase->classlike_storage_provider->get($attribute->fq_class_name);

        $has_attribute_attribute = $attribute->fq_class_name === 'Attribute';

        foreach ($attribute_class_storage->attributes as $attribute_attribute) {
            if ($attribute_attribute->fq_class_name === 'Attribute') {
                $has_attribute_attribute = true;

                if (!$attribute_attribute->args) {
                    return;
                }

                $first_arg = reset($attribute_attribute->args);

                $first_arg_type = $first_arg->type;

                if ($first_arg_type instanceof UnresolvedConstantComponent) {
                    $first_arg_type = new Union([
                        ConstantTypeResolver::resolve(
                            $codebase->classlikes,
                            $first_arg_type,
                            $source instanceof StatementsAnalyzer ? $source : null
                        )
                    ]);
                }

                if (!$first_arg_type->isSingleIntLiteral()) {
                    return;
                }

                $acceptable_mask = $first_arg_type->getSingleIntLiteral()->value;

                if (($acceptable_mask & $target) !== $target) {
                    $target_map = [
                        1 => 'class',
                        2 => 'function',
                        4 => 'method',
                        8 => 'property',
                        16 => 'class constant',
                        32 => 'function/method parameter'
                    ];

                    IssueBuffer::maybeAdd(
                        new InvalidAttribute(
                            'This attribute can not be used on a ' . $target_map[$target],
                            $attribute->name_location
                        ),
                        $source->getSuppressedIssues()
                    );
                }
            }
        }

        if (!$has_attribute_attribute) {
            IssueBuffer::maybeAdd(
                new InvalidAttribute(
                    'The class ' . $attribute->fq_class_name . ' doesn’t have the Attribute attribute',
                    $attribute->name_location
                ),
                $source->getSuppressedIssues()
            );
        }
    }
}
