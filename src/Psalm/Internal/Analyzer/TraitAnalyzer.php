<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;

/**
 * @internal
 */
class TraitAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @var Aliases
     */
    private $aliases;

    public function __construct(
        PhpParser\Node\Stmt\Trait_ $class,
        SourceAnalyzer $source,
        string $fq_class_name,
        Aliases $aliases
    ) {
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->aliases = $source->getAliases();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;
        $codebase = $source->getCodebase();
        $this->storage = $codebase->classlike_storage_provider->get($fq_class_name);
        $this->aliases = $aliases;
    }

    public function getNamespace(): ?string
    {
        return $this->aliases->namespace;
    }

    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    public function analyze()
    {
        $class = $this->class;

        if (!$class instanceof PhpParser\Node\Stmt\Trait_) {
            throw new \LogicException('Something went badly wrong');
        }

        $fq_class_name = $this->fq_class_name;
        $storage = $this->storage;

        if ($storage->has_visitor_issues) {
            return null;
        }

        $project_analyzer = $this->file_analyzer->project_analyzer;
        $codebase = $this->getCodebase();

        if ($codebase->alter_code && $class->name && $codebase->classes_to_move) {

            if (isset($codebase->classes_to_move[strtolower($this->fq_class_name)])) {
                $destination_class = $codebase->classes_to_move[strtolower($this->fq_class_name)];

                $source_class_parts = explode('\\', $this->fq_class_name);
                $destination_class_parts = explode('\\', $destination_class);

                array_pop($source_class_parts);
                array_pop($destination_class_parts);

                $source_ns = implode('\\', $source_class_parts);
                $destination_ns = implode('\\', $destination_class_parts);

                if (strtolower($source_ns) !== strtolower($destination_ns)) {

                    // If the trait already has a namespace
                    if ($storage->namespace_name_location) {

                        $bounds = $storage->namespace_name_location->getSelectionBounds();

                        $file_manipulations = [
                            new \Psalm\FileManipulation(
                                $bounds[0],
                                $bounds[1],
                                $destination_ns
                            )
                        ];

                        \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    } elseif (!$source_ns) {
                        $first_statement_pos = $this->getFileAnalyzer()->getFirstStatementOffset();

                        if ($first_statement_pos === -1) {
                            $first_statement_pos = (int) $class->getAttribute('startFilePos');
                        }

                        $file_manipulations = [
                            new \Psalm\FileManipulation(
                                $first_statement_pos,
                                $first_statement_pos,
                                'namespace ' . $destination_ns . ';' . "\n\n",
                                true
                            )
                        ];

                        \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    }
                }
            }

            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $class->name,
                $this->fq_class_name,
                null
            );
        }

        return;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return [];
    }
}
