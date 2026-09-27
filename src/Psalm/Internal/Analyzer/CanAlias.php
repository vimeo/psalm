<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Override;
use PhpParser;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Interner;

/**
 * @psalm-mutable
 * @internal
 */
trait CanAlias
{
    /**
     * @var array<int, int> lowercase alias id => class name id
     */
    private array $aliased_classes = [];

    /**
     * @var array<int, CodeLocation> lowercase alias id => location
     */
    private array $aliased_class_locations = [];

    /**
     * @var array<int, int> lowercase class name id => alias id
     */
    private array $aliased_classes_flipped = [];

    /**
     * @var array<int, int> lowercase class name id => alias id
     */
    private array $aliased_classes_flipped_replaceable = [];

    /**
     * @var array<int, int> lowercase alias id => function name id
     */
    private array $aliased_functions = [];

    /**
     * @var array<int, int> alias id => constant name id
     */
    private array $aliased_constants = [];

    public function visitUse(PhpParser\Node\Stmt\Use_ $stmt): void
    {
        $codebase = $this->getCodebase();

        foreach ($stmt->uses as $use) {
            $use_path_str = $use->name->toString();
            $use_path = Interner::intern($use_path_str);
            $use_path_lc = Interner::lower($use_path);
            $use_alias_str = $use->alias->name ?? $use->name->getLast();
            $use_alias = Interner::intern($use_alias_str);
            $use_alias_lc = Interner::lower($use_alias);

            switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $stmt->type) {
                case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                    $this->aliased_functions[$use_alias_lc] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                    $this->aliased_constants[$use_alias] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                    $codebase->analyzer->addOffsetReference(
                        $this->getFilePath(),
                        (int) $use->getAttribute('startFilePos'),
                        (int) $use->getAttribute('endFilePos'),
                        $use_path_str,
                    );
                    if ($codebase->collect_locations) {
                        // register the path
                        $codebase->use_referencing_locations[$use_path_lc][] =
                            new CodeLocation($this, $use);
                    }

                    if ($codebase->alter_code) {
                        if (isset($codebase->class_transforms[$use_path_lc])) {
                            $new_fq_class_name = $codebase->class_transforms[$use_path_lc];

                            $file_manipulations = [];

                            $file_manipulations[] = new FileManipulation(
                                (int) $use->getAttribute('startFilePos'),
                                (int) $use->getAttribute('endFilePos') + 1,
                                Interner::str($new_fq_class_name) . ($use->alias ? ' as ' . $use_alias_str : ''),
                            );

                            FileManipulationBuffer::add($this->getFilePath(), $file_manipulations);
                        }

                        $this->aliased_classes_flipped_replaceable[$use_path_lc] = $use_alias;
                    }

                    $this->aliased_classes[$use_alias_lc] = $use_path;
                    $this->aliased_class_locations[$use_alias_lc] = new CodeLocation($this, $stmt);
                    $this->aliased_classes_flipped[$use_path_lc] = $use_alias;
                    break;
            }
        }
    }

    public function visitGroupUse(PhpParser\Node\Stmt\GroupUse $stmt): void
    {
        $use_prefix = $stmt->prefix->toString();

        $codebase = $this->getCodebase();

        foreach ($stmt->uses as $use) {
            $use_path = Interner::intern($use_prefix . '\\' . $use->name->toString());
            $use_alias = Interner::intern($use->alias->name ?? $use->name->getLast());

            switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $stmt->type) {
                case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                    $this->aliased_functions[Interner::lower($use_alias)] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                    $this->aliased_constants[$use_alias] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                    if ($codebase->collect_locations) {
                        // register the path
                        $codebase->use_referencing_locations[Interner::lower($use_path)][] =
                            new CodeLocation($this, $use);
                    }

                    $this->aliased_classes[Interner::lower($use_alias)] = $use_path;
                    $this->aliased_classes_flipped[Interner::lower($use_path)] = $use_alias;
                    break;
            }
        }
    }

    /**
     * @psalm-mutation-free
     * @return array<int, int>
     */
    #[Override]
    public function getAliasedClassesFlipped(): array
    {
        return $this->aliased_classes_flipped;
    }

    /**
     * @psalm-mutation-free
     * @return array<int, int>
     */
    #[Override]
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return $this->aliased_classes_flipped_replaceable;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getAliases(): Aliases
    {
        return new Aliases(
            $this->getNamespace(),
            $this->aliased_classes,
            $this->aliased_functions,
            $this->aliased_constants,
        );
    }
}
