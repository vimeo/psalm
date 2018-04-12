<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\FileIncludeException;
use Psalm\Issue\MissingFile;
use Psalm\Issue\UnresolvableInclude;
use Psalm\IssueBuffer;

class IncludeChecker
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Include_ $stmt,
        Context $context
    ) {
        $config = Config::getInstance();

        if (!$config->allow_includes) {
            throw new FileIncludeException(
                'File includes are not allowed per your Psalm config - check the allowFileIncludes flag.'
            );
        }

        if (ExpressionChecker::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = str_replace('/', DIRECTORY_SEPARATOR, $stmt->expr->value);

            // attempts to resolve using get_include_path dirs
            $include_path = self::resolveIncludePath($path_to_file, dirname($statements_checker->getCheckedFileName()));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if (DIRECTORY_SEPARATOR === '/') {
                $is_path_relative = $path_to_file[0] !== DIRECTORY_SEPARATOR;
            } else {
                $is_path_relative = !preg_match('~^[A-Z]:\\\\~i', $path_to_file);
            }

            if ($is_path_relative) {
                $path_to_file = getcwd() . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = self::getPathTo($stmt->expr, $statements_checker->getCheckedFileName());
        }

        if ($path_to_file) {
            $slash = preg_quote(DIRECTORY_SEPARATOR, '/');
            $reduce_pattern = '/' . $slash . '[^' . $slash . ']+' . $slash . '\.\.' . $slash . '/';

            while (preg_match($reduce_pattern, $path_to_file)) {
                $path_to_file = preg_replace($reduce_pattern, DIRECTORY_SEPARATOR, $path_to_file);
            }

            // if the file is already included, we can't check much more
            if (in_array(realpath($path_to_file), get_included_files(), true)) {
                return null;
            }

            if ($statements_checker->getFilePath() === $path_to_file) {
                return null;
            }

            $current_file_checker = $statements_checker->getFileChecker();

            if ($current_file_checker->project_checker->fileExists($path_to_file)) {
                if (is_subclass_of($current_file_checker, 'Psalm\\Checker\\FileChecker')) {
                    $statements_checker->analyze(
                        $current_file_checker->project_checker->codebase->getStatementsForFile($path_to_file),
                        $context
                    );
                }

                return null;
            }

            $source = $statements_checker->getSource();

            if (IssueBuffer::accepts(
                new MissingFile(
                    'Cannot find file ' . $path_to_file . ' to include',
                    new CodeLocation($source, $stmt)
                ),
                $source->getSuppressedIssues()
            )) {
                // fall through
            }
        } else {
            $source = $statements_checker->getSource();

            if (IssueBuffer::accepts(
                new UnresolvableInclude(
                    'Cannot resolve the given expression to a file path',
                    new CodeLocation($source, $stmt)
                ),
                $source->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        $context->check_classes = false;
        $context->check_variables = false;
        $context->check_functions = false;

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @param  string              $file_name
     *
     * @return string|null
     * @psalm-suppress MixedAssignment
     */
    public static function getPathTo(PhpParser\Node\Expr $stmt, $file_name)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            $is_path_relative = $file_name[0] !== DIRECTORY_SEPARATOR;
        } else {
            $is_path_relative = !preg_match('~^[A-Z]:\\\\~i', $file_name);
        }

        if ($is_path_relative) {
            $file_name = getcwd() . DIRECTORY_SEPARATOR . $file_name;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return $stmt->value;
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $left_string = self::getPathTo($stmt->left, $file_name);
            $right_string = self::getPathTo($stmt->right, $file_name);

            if ($left_string && $right_string) {
                return $left_string . $right_string;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name &&
            $stmt->name->parts === ['dirname']
        ) {
            if ($stmt->args) {
                $evaled_path = self::getPathTo($stmt->args[0]->value, $file_name);

                if (!$evaled_path) {
                    return null;
                }

                return dirname($evaled_path);
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch && $stmt->name instanceof PhpParser\Node\Name) {
            $const_name = implode('', $stmt->name->parts);

            if (defined($const_name)) {
                $constant_value = constant($const_name);

                if (is_string($constant_value)) {
                    return $constant_value;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir) {
            return dirname($file_name);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File) {
            return $file_name;
        }

        return null;
    }

    /**
     * @param   string  $file_name
     * @param   string  $current_directory
     *
     * @return  string|null
     */
    public static function resolveIncludePath($file_name, $current_directory)
    {
        if (!$current_directory) {
            return $file_name;
        }

        $paths = PATH_SEPARATOR == ':'
            ? preg_split('#(?<!phar):#', get_include_path())
            : explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $prefix) {
            $ds = substr($prefix, -1) == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;

            if ($prefix === '.') {
                $prefix = $current_directory;
            }

            $file = $prefix . $ds . $file_name;

            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }
}
