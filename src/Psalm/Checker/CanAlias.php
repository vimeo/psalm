<?php
namespace Psalm\Checker;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

trait CanAlias
{
    /**
     * @var array<string, string>
     */
    private $aliased_classes = [];

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
                    // register classlike use, if it exists
                    $this->aliased_classes[strtolower($use->alias)] = $use_path;
                    $this->aliased_classes_flipped[strtolower($use_path)] = $use->alias;
                    break;
            }
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\GroupUse $stmt
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
                    $this->aliased_classes[strtolower($use->alias)] = $use_path;
                    $this->aliased_classes_flipped[strtolower($use_path)] = $use->alias;
                    break;
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClasses()
    {
        return $this->aliased_classes;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        return $this->aliased_classes_flipped;
    }

    /**
     * Gets a list of all aliased constants
     *
     * @return array<string, string>
     */
    public function getAliasedConstants()
    {
        return $this->aliased_constants;
    }

    /**
     * Gets a list of all aliased functions
     *
     * @return array<string, string>
     */
    public function getAliasedFunctions()
    {
        return $this->aliased_functions;
    }
}
