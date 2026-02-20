<?php

declare(strict_types=1);

namespace Psalm\Internal\FileManipulation;

use PhpParser\Node\Stmt\ClassLike;
use Psalm\DocComment;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Storage\Mutations;

use function ltrim;
use function str_replace;
use function strlen;
use function strrpos;
use function substr;

/**
 * @internal
 */
final class ClassDocblockManipulator
{
    /**
     * @var array<string, array<int, self>>
     */
    private static array $manipulators = [];

    private readonly int $docblock_start;

    private readonly int $docblock_end;

    /**
     * @var Mutations::LEVEL_*|null
     */
    private ?int $allowed_mutations = null;

    private readonly string $indentation;

    public static function getForClass(
        ProjectAnalyzer $project_analyzer,
        string $file_path,
        ClassLike $stmt,
    ): self {
        if (isset(self::$manipulators[$file_path][$stmt->getLine()])) {
            return self::$manipulators[$file_path][$stmt->getLine()];
        }

        $manipulator
            = self::$manipulators[$file_path][$stmt->getLine()]
            = new self($project_analyzer, $stmt, $file_path);

        return $manipulator;
    }

    private function __construct(
        ProjectAnalyzer $project_analyzer,
        private readonly ClassLike $stmt,
        string $file_path,
    ) {
        $docblock = $stmt->getDocComment();
        $this->docblock_start = $docblock ? $docblock->getStartFilePos() : (int)$stmt->getAttribute('startFilePos');
        $this->docblock_end = (int)$stmt->getAttribute('startFilePos');

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($file_path);

        $preceding_newline_pos = (int) strrpos($file_contents, "\n", $this->docblock_end - strlen($file_contents));

        $first_line = substr($file_contents, $preceding_newline_pos + 1, $this->docblock_end - $preceding_newline_pos);

        $this->indentation = str_replace(ltrim($first_line), '', $first_line);
    }

    /**
     * @param Mutations::LEVEL_* $allowed_mutations
     * @psalm-external-mutation-free
     */
    public function setAllowedMutations(int $allowed_mutations): void
    {
        $this->allowed_mutations = $allowed_mutations;
    }

    /**
     * Gets a new docblock given the existing docblock, if one exists, and the updated return types
     * and/or parameters
     */
    private function getDocblock(): string
    {
        $docblock = $this->stmt->getDocComment();

        if ($docblock) {
            $parsed_docblock = DocComment::parsePreservingLength($docblock);
        } else {
            $parsed_docblock = new ParsedDocblock('', []);
        }

        $modified_docblock = false;

        if ($this->allowed_mutations !== null) {
            $modified_docblock = true;

            unset($parsed_docblock->tags['psalm-pure']);
            unset($parsed_docblock->tags['psalm-immutable']);
            unset($parsed_docblock->tags['psalm-external-mutation-free']);
            unset($parsed_docblock->tags['psalm-mutable']);

            if ($this->allowed_mutations === Mutations::LEVEL_NONE) {
                $parsed_docblock->tags['psalm-pure'] = [''];
            } elseif ($this->allowed_mutations === Mutations::LEVEL_INTERNAL_READ) {
                $parsed_docblock->tags['psalm-immutable'] = [''];
            } elseif ($this->allowed_mutations === Mutations::LEVEL_INTERNAL_READ_WRITE) {
                $parsed_docblock->tags['psalm-external-mutation-free'] = [''];
            } else {
                $parsed_docblock->tags['psalm-mutable'] = [''];
            }
        }

        if (!$modified_docblock) {
            return (string)$docblock . "\n" . $this->indentation;
        }

        return $parsed_docblock->render($this->indentation);
    }

    /**
     * @return array<int, FileManipulation>
     */
    public static function getManipulationsForFile(string $file_path): array
    {
        if (!isset(self::$manipulators[$file_path])) {
            return [];
        }

        $file_manipulations = [];

        foreach (self::$manipulators[$file_path] as $manipulator) {
            if ($manipulator->allowed_mutations !== null) {
                $file_manipulations[$manipulator->docblock_start] = new FileManipulation(
                    $manipulator->docblock_start,
                    $manipulator->docblock_end,
                    $manipulator->getDocblock(),
                );
            }
        }

        return $file_manipulations;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$manipulators = [];
    }
}
