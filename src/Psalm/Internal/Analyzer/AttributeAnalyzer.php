<?php

namespace Psalm\Internal\Analyzer;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Issue\InvalidAttribute;
use Psalm\IssueBuffer;
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
        AttributeGroup $attribute_group,
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

        $statements_analyzer = new StatementsAnalyzer(
            $source,
            new NodeDataProvider()
        );

        $statements_analyzer->analyze(self::attributeGroupToStmts($attribute_group), new Context());
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

    /**
     * @return list<Stmt>
     */
    private static function attributeGroupToStmts(AttributeGroup $attribute_group): array
    {
        $stmts = [];
        foreach ($attribute_group->attrs as $attr) {
            $stmts[] = new Expression(new New_($attr->name, $attr->args, $attr->getAttributes()));
        }
        return $stmts;
    }
}
