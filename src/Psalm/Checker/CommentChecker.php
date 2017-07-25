<?php
namespace Psalm\Checker;

use Psalm\Aliases;
use Psalm\ClassLikeDocblockComment;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FunctionDocblockComment;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\VarDocblockComment;

class CommentChecker
{
    const TYPE_REGEX = '(\??\\\?[A-Za-z][\(\)A-Za-z0-9_\<,\>\[\]\-\{\}:|\\\]*|\$[a-zA-Z_0-9_\<,\>\|\[\]-\{\}:]+)';

    /**
     * @param  string           $comment
     * @param  Context|null     $context
     * @param  StatementsSource $source
     * @param  Aliases          $aliases
     * @param  array<string, string>|null   $template_types
     * @param  int|null         $var_line_number
     * @param  int|null         $came_from_line_number what line number in $source that $comment came from
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return VarDocblockComment|null
     * @psalm-suppress MixedArrayAccess
     */
    public static function getTypeFromComment(
        $comment,
        $context,
        StatementsSource $source,
        Aliases $aliases,
        array $template_types = null,
        $var_line_number = null,
        $came_from_line_number = null
    ) {
        $var_id = null;

        $var_type_string = null;

        $comments = self::parseDocComment($comment, $var_line_number);

        if (!isset($comments['specials']['var'])) {
            return;
        }

        if ($comments) {
            /** @var int $line_number */
            foreach ($comments['specials']['var'] as $line_number => $var_line) {
                $var_line = trim($var_line);

                if (!$var_line) {
                    continue;
                }

                try {
                    $line_parts = self::splitDocLine($var_line);
                } catch (DocblockParseException $e) {
                    throw $e;
                }

                if ($line_parts && $line_parts[0]) {
                    $var_type_string = FunctionLikeChecker::fixUpLocalType(
                        $line_parts[0],
                        $aliases,
                        $template_types
                    );

                    $var_line_number = $line_number;

                    // support PHPStorm-style docblocks like
                    // @var Type $variable
                    if (count($line_parts) > 1 && $line_parts[1][0] === '$') {
                        $var_id = $line_parts[1];
                    }

                    break;
                }
            }
        }

        if (!$var_type_string) {
            return null;
        }

        try {
            $defined_type = Type::parseString($var_type_string);
        } catch (TypeParseTreeException $e) {
            if (is_int($came_from_line_number)) {
                throw new DocblockParseException(
                    $var_type_string .
                    ' is not a valid type' .
                    ' (from ' .
                    $source->getCheckedFilePath() .
                    ':' .
                    $came_from_line_number .
                    ')'
                );
            }

            throw new DocblockParseException($var_type_string . ' is not a valid type');
        }

        $defined_type->setFromDocblock();

        $var_comment = new VarDocblockComment();
        $var_comment->type = $defined_type;
        $var_comment->var_id = $var_id;
        $var_comment->line_number = $var_line_number;
        $var_comment->deprecated = isset($comments['specials']['deprecated']);

        return $var_comment;
    }

    /**
     * @param  string  $comment
     * @param  int     $line_number
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return FunctionDocblockComment
     * @psalm-suppress MixedArrayAccess
     */
    public static function extractFunctionDocblockInfo($comment, $line_number)
    {
        $comments = self::parseDocComment($comment, $line_number);

        $info = new FunctionDocblockComment();

        if (isset($comments['specials']['return']) || isset($comments['specials']['psalm-return'])) {
            /** @var array<int, string> */
            $return_specials = isset($comments['specials']['psalm-return'])
                ? $comments['specials']['psalm-return']
                : $comments['specials']['return'];

            $return_block = trim((string)reset($return_specials));

            try {
                $line_parts = self::splitDocLine($return_block);
            } catch (DocblockParseException $e) {
                throw $e;
            }

            if (preg_match('/^' . self::TYPE_REGEX . '$/', $line_parts[0])
                && !preg_match('/\[[^\]]+\]/', $line_parts[0])
                && !strpos($line_parts[0], '::')
                && $line_parts[0][0] !== '{'
            ) {
                if ($line_parts[0][0] === '$' && $line_parts[0] !== '$this') {
                    throw new DocblockParseException('Badly-formatted @param type');
                }

                $info->return_type = $line_parts[0];
                $line_number = array_keys($return_specials)[0];

                if ($line_number) {
                    $info->return_type_line_number = $line_number;
                }
            } else {
                throw new DocblockParseException('Badly-formatted @return type');
            }
        }

        if (isset($comments['specials']['param'])) {
            /** @var string $param */
            foreach ($comments['specials']['param'] as $line_number => $param) {
                try {
                    $line_parts = self::splitDocLine($param);
                } catch (DocblockParseException $e) {
                    throw $e;
                }

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    array_unshift($line_parts, 'mixed');
                }

                if (count($line_parts) > 1) {
                    if (preg_match('/^' . self::TYPE_REGEX . '$/', $line_parts[0])
                        && !preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && !strpos($line_parts[0], '::')
                        && $line_parts[0][0] !== '{'
                    ) {
                        if ($line_parts[1][0] === '&') {
                            $line_parts[1] = substr($line_parts[1], 1);
                        }

                        if ($line_parts[0][0] === '$' && $line_parts[0] !== '$this') {
                            throw new DocblockParseException('Badly-formatted @param type');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->params[] = [
                            'name' => $line_parts[1],
                            'type' => $line_parts[0],
                            'line_number' => $line_number,
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($comments['specials']['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($comments['specials']['psalm-suppress'])) {
            /** @var string $suppress_entry */
            foreach ($comments['specials']['psalm-suppress'] as $suppress_entry) {
                $info->suppress[] = preg_split('/[\s]+/', $suppress_entry)[0];
            }
        }

        if (isset($comments['specials']['template'])) {
            /** @var string $suppress_entry */
            foreach ($comments['specials']['template'] as $template_line) {
                $template_type = preg_split('/[\s]+/', $template_line);

                if (count($template_type) > 2 && in_array(strtolower($template_type[1]), ['as', 'super'], true)) {
                    $info->template_types[] = [$template_type[0], strtolower($template_type[1]), $template_type[2]];
                } else {
                    $info->template_types[] = [$template_type[0]];
                }
            }
        }

        if (isset($comments['specials']['template-typeof'])) {
            /** @var string $suppress_entry */
            foreach ($comments['specials']['template-typeof'] as $template_typeof) {
                $typeof_parts = preg_split('/[\s]+/', $template_typeof);

                if (count($typeof_parts) < 2 || $typeof_parts[1][0] !== '$') {
                    throw new DocblockParseException('Badly-formatted @template-typeof');
                }

                $info->template_typeofs[] = [
                    'template_type' => $typeof_parts[0],
                    'param_name' => substr($typeof_parts[1], 1),
                ];
            }
        }

        $info->variadic = isset($comments['specials']['psalm-variadic']);
        $info->ignore_nullable_return = isset($comments['specials']['psalm-ignore-nullable-return']);

        return $info;
    }

    /**
     * @param  string  $comment
     * @param  int     $line_number
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return ClassLikeDocblockComment
     * @psalm-suppress MixedArrayAccess
     */
    public static function extractClassLikeDocblockInfo($comment, $line_number)
    {
        $comments = self::parseDocComment($comment, $line_number);

        $info = new ClassLikeDocblockComment();

        if (isset($comments['specials']['template'])) {
            /** @var string $suppress_entry */
            foreach ($comments['specials']['template'] as $template_line) {
                $template_type = preg_split('/[\s]+/', $template_line);

                if (count($template_type) > 2 && in_array(strtolower($template_type[1]), ['as', 'super'], true)) {
                    $info->template_types[] = [$template_type[0], strtolower($template_type[1]), $template_type[2]];
                } else {
                    $info->template_types[] = [$template_type[0]];
                }
            }
        }

        if (isset($comments['specials']['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($comments['specials']['property'])) {
            /** @var string $property */
            foreach ($comments['specials']['property'] as $line_number => $property) {
                try {
                    $line_parts = self::splitDocLine($property);
                } catch (DocblockParseException $e) {
                    throw $e;
                }

                if (count($line_parts) === 1 && $line_parts[0][0] === '$') {
                    array_unshift($line_parts, 'mixed');
                }

                if (count($line_parts) > 1) {
                    if (preg_match('/^' . self::TYPE_REGEX . '$/', $line_parts[0])
                        && !preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && !strpos($line_parts[0], '::')
                        && $line_parts[0][0] !== '{'
                    ) {
                        if ($line_parts[1][0] === '&') {
                            $line_parts[1] = substr($line_parts[1], 1);
                        }

                        if ($line_parts[0][0] === '$' && $line_parts[0] !== '$this') {
                            throw new DocblockParseException('Badly-formatted @param type');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->properties[] = [
                            'name' => $line_parts[1],
                            'type' => $line_parts[0],
                            'line_number' => $line_number,
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        return $info;
    }

    /**
     * @param  string $return_block
     *
     * @throws DocblockParseException if an invalid string is found
     *
     * @return array<string>
     */
    protected static function splitDocLine($return_block)
    {
        $brackets = '';

        $type = '';

        for ($i = 0; $i < strlen($return_block); ++$i) {
            $char = $return_block[$i];

            if ($char === '[' || $char === '{' || $char === '(' || $char === '<') {
                $brackets .= $char;
            } elseif ($char === ']' || $char === '}' || $char === ')' || $char === '>') {
                $last_bracket = substr($brackets, -1);
                $brackets = substr($brackets, 0, -1);

                if (($char === ']' && $last_bracket !== '[')
                    || ($char === '}' && $last_bracket !== '{')
                    || ($char === ')' && $last_bracket !== '(')
                    || ($char === '>' && $last_bracket !== '<')
                ) {
                    throw new DocblockParseException('Invalid string ' . $return_block);
                }
            } elseif ($char === ' ' || $char === "\t") {
                if ($brackets) {
                    continue;
                }

                $remaining = trim(substr($return_block, $i + 1));

                if ($remaining) {
                    return array_merge([$type], preg_split('/[\s\t]+/', $remaining));
                }

                return [$type];
            }

            $type .= $char;
        }

        return [$type];
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker, which was taken from
     * https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @param  string  $docblock
     * @param  int     $line_number
     *
     * @return array Array of the main comment and specials
     * @psalm-return array{description:string, specials:array<string, array<mixed, string>>}
     */
    public static function parseDocComment($docblock, $line_number = null)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^[ \t]*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $line_map = [];

        /** @var int|false */
        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = rtrim($old_last_line) . ' ' . trim($line);

                if ($line_number) {
                    $old_line_number = $line_map[$old_last_line];
                    unset($line_map[$old_last_line]);
                    $line_map[$lines[$last]] = $old_line_number;
                }

                unset($lines[$k]);
            }

            if ($line_number) {
                $line_map[$line] = $line_number++;
            }
        }

        $docblock = implode("\n", $lines);

        $special = [];

        // Parse @specials.
        $matches = [];
        $have_specials = preg_match_all('/^\s?@([\w\-:]+)[\t ]*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER);
        if ($have_specials) {
            $docblock = preg_replace('/^\s?@([\w\-:]+)\s*([^\n]*)/m', '', $docblock);
            /** @var string[] $match */
            foreach ($matches as $m => $match) {
                list($_, $type, $data) = $match;

                if (empty($special[$type])) {
                    $special[$type] = [];
                }

                $line_number = $line_map && isset($line_map[$_]) ? $line_map[$_] : (int)$m;

                $special[$type][$line_number] = $data;
            }
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0; $ii < strlen($line); ++$ii) {
                if ($line[$ii] != ' ') {
                    break;
                }
                ++$indent;
            }

            /** @var int */
            $min_indent = min($indent, $min_indent);
        }

        $docblock = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $docblock);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock);

        return [
            'description' => $docblock,
            'specials' => $special,
        ];
    }

    /**
     * @param  array{description:string,specials:array<string,array<string>>} $parsed_doc_comment
     * @param  string                                                         $left_padding
     *
     * @return array<int, string>
     */
    public static function renderDocComment(array $parsed_doc_comment, $left_padding)
    {
        $doc_comment_text = [$left_padding . '/**'];

        $description_lines = null;

        $trimmed_description = trim($parsed_doc_comment['description']);

        if (!empty($trimmed_description)) {
            $description_lines = explode(PHP_EOL, $parsed_doc_comment['description']);

            foreach ($description_lines as $line) {
                $doc_comment_text[] = $left_padding . ' * ' . $line;
            }
        }

        if ($description_lines && $parsed_doc_comment['specials']) {
            $doc_comment_text[] = $left_padding . ' *';
        }

        if ($parsed_doc_comment['specials']) {
            $special_type_lengths = array_map('strlen', array_keys($parsed_doc_comment['specials']));
            /** @var int */
            $special_type_width = max($special_type_lengths) + 1;

            foreach ($parsed_doc_comment['specials'] as $type => $lines) {
                foreach ($lines as $line) {
                    $doc_comment_text[] = $left_padding . ' * @' . str_pad($type, $special_type_width) . $line;
                }
            }
        }

        $doc_comment_text[] = $left_padding . ' */';

        return $doc_comment_text;
    }
}
