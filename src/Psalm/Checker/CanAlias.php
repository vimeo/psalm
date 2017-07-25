<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Aliases;
use Psalm\CodeLocation;

trait CanAlias
{
    /**
     * @var array<string, string>
     */
    private $aliased_classes = [];

    /**
     * @var array<string, CodeLocation>
     */
    private $aliased_class_locations = [];

    /**
     * @var array<string, string>
     */
    private $aliased_classes_flipped = [];

    /**
     * @var array<string, string>
     */
    private $aliased_functions = [];

    /**
     * @var array<string, string>
     */
    private $aliased_constants = [];

    /**
     * @param  PhpParser\Node\Stmt\Use_ $stmt
     *
     * @return void
     */
    public function visitUse(PhpParser\Node\Stmt\Use_ $stmt)
    {
        foreach ($stmt->uses as $use) {
            $use_path = implode('\\', $use->name->parts);

            switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $stmt->type) {
                case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                    $this->aliased_functions[strtolower($use->alias)] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                    $this->aliased_constants[$use->alias] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                    if ($this->getFileChecker()->project_checker->collect_references) {
                        // register the path
                        $project_checker = $this->getFileChecker()->project_checker;

                        $project_checker->use_referencing_locations[strtolower($use_path)][$this->getFilePath()][] =
                            new \Psalm\CodeLocation($this, $use);

                        $project_checker->use_referencing_locations[$this->getFilePath()][strtolower($use_path)] = true;
                    }

                    $this->aliased_classes[strtolower($use->alias)] = $use_path;
                    $this->aliased_class_locations[strtolower($use->alias)] = new CodeLocation($this, $stmt);
                    $this->aliased_classes_flipped[strtolower($use_path)] = $use->alias;
                    break;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\GroupUse $stmt
     *
     * @return void
     */
    public function visitGroupUse(PhpParser\Node\Stmt\GroupUse $stmt)
    {
        $use_prefix = implode('\\', $stmt->prefix->parts);

        foreach ($stmt->uses as $use) {
            $use_path = $use_prefix . '\\' . implode('\\', $use->name->parts);

            switch ($use->type !== PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $stmt->type) {
                case PhpParser\Node\Stmt\Use_::TYPE_FUNCTION:
                    $this->aliased_functions[strtolower($use->alias)] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_CONSTANT:
                    $this->aliased_constants[$use->alias] = $use_path;
                    break;

                case PhpParser\Node\Stmt\Use_::TYPE_NORMAL:
                    if ($this->getFileChecker()->project_checker->collect_references) {
                        // register the path
                        $project_checker = $this->getFileChecker()->project_checker;

                        $project_checker->use_referencing_locations[$use_path][$this->getFilePath()] =
                            new \Psalm\CodeLocation($this, $use);
                    }

                    $this->aliased_classes[strtolower($use->alias)] = $use_path;
                    $this->aliased_classes_flipped[strtolower($use_path)] = $use->alias;
                    break;
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        return $this->aliased_classes_flipped;
    }

    public function getAliases()
    {
        return new Aliases(
            $this->getNamespace(),
            $this->aliased_classes,
            $this->aliased_functions,
            $this->aliased_constants
        );
    }
}
