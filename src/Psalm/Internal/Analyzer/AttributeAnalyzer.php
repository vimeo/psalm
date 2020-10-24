<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Storage\AttributeStorage;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

class AttributeAnalyzer
{
    /**
     * @param  array<string>    $suppressed_issues
     */
    public static function analyze(
        SourceAnalyzer $source,
        AttributeStorage $attribute,
        array $suppressed_issues
    ) : void {
        if ($attribute->fq_class_name === 'Attribute') {
            return;
        }

        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $source,
            $attribute->fq_class_name,
            $attribute->location,
            null,
            null,
            $suppressed_issues,
            false,
            false,
            false,
            false,
            true
        ) === false) {
            return;
        }

        $codebase = $source->getCodebase();

        if (!$codebase->classlikes->classExists($attribute->fq_class_name)) {
            return;
        }

        $node_args = [];

        foreach ($attribute->args as $storage_arg) {
            $type = $storage_arg->type;

            if ($type instanceof UnresolvedConstantComponent) {
                $type = new Union([
                    \Psalm\Internal\Codebase\ConstantTypeResolver::resolve(
                        $codebase->classlikes,
                        $type,
                        $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer ? $source : null
                    )
                ]);
            }

            if ($type->isMixed()) {
                return;
            }

            $node_args[] = new PhpParser\Node\Arg(
                \Psalm\Internal\Stubs\Generator\StubsGenerator::getExpressionFromType(
                    $type
                ),
                false,
                false,
                [
                    'startFilePos' => $storage_arg->location->raw_file_start,
                    'endFilePos' => $storage_arg->location->raw_file_end,
                    'startLine' => $storage_arg->location->raw_line_number
                ],
                $storage_arg->name
                    ? new PhpParser\Node\Identifier(
                        $storage_arg->name,
                        [
                            'startFilePos' => $storage_arg->location->raw_file_start,
                            'endFilePos' => $storage_arg->location->raw_file_end,
                            'startLine' => $storage_arg->location->raw_line_number
                        ]
                    )
                    : null
            );
        }

        $new_stmt = new PhpParser\Node\Expr\New_(
            new PhpParser\Node\Name\FullyQualified(
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
            new \Psalm\Internal\Provider\NodeDataProvider()
        );

        $statements_analyzer->analyze(
            [new PhpParser\Node\Stmt\Expression($new_stmt)],
            new \Psalm\Context()
        );
    }
}
