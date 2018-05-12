<?php
namespace Psalm\Checker;

use Psalm\Aliases;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Scanner\ClassLikeDocblockComment;
use Psalm\Scanner\FunctionDocblockComment;
use Psalm\Scanner\VarDocblockComment;
use Psalm\Type;

class CommentChecker
{
    const TYPE_REGEX = '(\??\\\?[\(\)A-Za-z0-9_&\<\.=,\>\[\]\-\{\}:|?\\\\]*|\$[a-zA-Z_0-9_]+)';

    /**
     * @param  string           $comment
     * @param  Aliases          $aliases
     * @param  array<string, string>|null   $template_types
     * @param  int|null         $var_line_number
     * @param  int|null         $came_from_line_number what line number in $source that $comment came from
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return VarDocblockComment[]
     * @psalm-suppress MixedArrayAccess
     */
    public static function getTypeFromComment(
        $comment,
        FileSource $source,
        Aliases $aliases,
        array $template_types = null,
        $var_line_number = null,
        $came_from_line_number = null
    ) {
        $var_id = null;

        $var_type_string = null;
        $original_type = null;

        $var_comments = [];
        $comments = self::parseDocComment($comment, $var_line_number);

        if (!isset($comments['specials']['var']) && !isset($comments['specials']['psalm-var'])) {
            return [];
        }

        if ($comments) {
            $all_vars = (isset($comments['specials']['var']) ? $comments['specials']['var'] : [])
                + (isset($comments['specials']['psalm-var']) ? $comments['specials']['psalm-var'] : []);

            /** @var int $line_number */
            foreach ($all_vars as $line_number => $var_line) {
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
                    if ($line_parts[0][0] === '$' && $line_parts[0] !== '$this') {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    try {
                        $var_type_string = Type::fixUpLocalType(
                            $line_parts[0],
                            $aliases,
                            $template_types
                        );
                    } catch (TypeParseTreeException $e) {
                        throw new DocblockParseException($line_parts[0] . ' is not a valid type');
                    }

                    $original_type = $line_parts[0];

                    $var_line_number = $line_number;

                    if (count($line_parts) > 1 && $line_parts[1][0] === '$') {
                        $var_id = $line_parts[1];
                    }
                }

                if (!$var_type_string || !$original_type) {
                    continue;
                }

                try {
                    $defined_type = Type::parseString($var_type_string, false, $template_types ?: []);
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
                $var_comment->original_type = $original_type;
                $var_comment->var_id = $var_id;
                $var_comment->line_number = $var_line_number;
                $var_comment->deprecated = isset($comments['specials']['deprecated']);

                $var_comments[] = $var_comment;
            }
        }

        return $var_comments;
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

            if (!$return_block) {
                throw new DocblockParseException('Missing @return type');
            }

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
                if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                    throw new IncorrectDocblockException('Misplaced variable');
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

        if (isset($comments['specials']['param']) || isset($comments['specials']['psalm-param'])) {
            $all_params = (isset($comments['specials']['param']) ? $comments['specials']['param'] : [])
                + (isset($comments['specials']['psalm-param']) ? $comments['specials']['psalm-param'] : []);

            /** @var string $param */
            foreach ($all_params as $line_number => $param) {
                try {
                    $line_parts = self::splitDocLine($param);
                } catch (DocblockParseException $e) {
                    throw $e;
                }

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    continue;
                }

                if (count($line_parts) > 1) {
                    if (preg_match('/^' . self::TYPE_REGEX . '$/', $line_parts[0])
                        && !preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && !strpos($line_parts[0], '::')
                        && $line_parts[0][0] !== '{'
                        && !in_array($line_parts[0], ['null', 'false', 'true'], true)
                    ) {
                        if ($line_parts[1][0] === '&') {
                            $line_parts[1] = substr($line_parts[1], 1);
                        }

                        if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->params[] = [
                            'name' => $line_parts[1],
                            'type' => $line_parts[0],
                            'line_number' => (int)$line_number,
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
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->template_typeofs[] = [
                    'template_type' => $typeof_parts[0],
                    'param_name' => substr($typeof_parts[1], 1),
                ];
            }
        }

        $info->variadic = isset($comments['specials']['psalm-variadic']);
        $info->ignore_nullable_return = isset($comments['specials']['psalm-ignore-nullable-return']);
        $info->ignore_falsable_return = isset($comments['specials']['psalm-ignore-falsable-return']);

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

        if (isset($comments['specials']['psalm-seal-properties'])) {
            $info->sealed_properties = true;
        }

        if (isset($comments['specials']['psalm-seal-methods'])) {
            $info->sealed_methods = true;
        }

        if (isset($comments['specials']['psalm-suppress'])) {
            /** @var string $suppress_entry */
            foreach ($comments['specials']['psalm-suppress'] as $suppress_entry) {
                $info->suppressed_issues[] = preg_split('/[\s]+/', $suppress_entry)[0];
            }
        }

        if (isset($comments['specials']['method'])) {
            /** @var string $method_entry */
            foreach ($comments['specials']['method'] as $method_entry) {
                $method_entry = preg_replace('/[ \t]+/', ' ', trim($method_entry));

                $return_docblock = '';

                if (!preg_match('/^([a-z_A-Z][a-z_0-9A-Z]+) *\(/', $method_entry, $matches)) {
                    $doc_line_parts = self::splitDocLine($method_entry);

                    $return_docblock = '/** @return ' . array_shift($doc_line_parts) . ' */';

                    $method_entry = implode(' ', $doc_line_parts);
                }

                $method_entry = trim(preg_replace('/\/\/.*/', '', $method_entry));

                $end_of_method_regex = '/(?<!array\()\) ?(\: ?(\??[\\\\a-zA-Z0-9_]+))?/';

                if (preg_match($end_of_method_regex, $method_entry, $matches, PREG_OFFSET_CAPTURE)) {
                    $method_entry = substr($method_entry, 0, (int) $matches[0][1] + strlen((string) $matches[0][0]));
                }

                $php_string = '<?php ' . $return_docblock . ' function ' . $method_entry . '{}';

                try {
                    $statements = \Psalm\Provider\StatementsProvider::parseStatements($php_string);
                } catch (\Exception $e) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                if (!$statements[0] instanceof \PhpParser\Node\Stmt\Function_) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                $info->methods[] = $statements[0];
            }
        }

        self::addMagicPropertyToInfo($info, $comments['specials'], 'property');
        self::addMagicPropertyToInfo($info, $comments['specials'], 'property-read');
        self::addMagicPropertyToInfo($info, $comments['specials'], 'property-write');

        return $info;
    }

    /**
     * @param ClassLikeDocblockComment $info
     * @param array<string, array<int, string>> $specials
     * @param string $property_tag ('property', 'property-read', or 'property-write')
     *
     * @throws DocblockParseException
     *
     * @return void
     */
    protected static function addMagicPropertyToInfo(ClassLikeDocblockComment $info, array $specials, $property_tag)
    {
        $magic_property_comments = isset($specials[$property_tag]) ? $specials[$property_tag] : [];
        foreach ($magic_property_comments as $line_number => $property) {
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

                    if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                    $info->properties[] = [
                        'name' => $line_parts[1],
                        'type' => $line_parts[0],
                        'line_number' => $line_number,
                        'tag' => $property_tag,
                    ];
                } else {
                    throw new DocblockParseException('Badly-formatted @property');
                }
            } else {
                throw new DocblockParseException('Badly-formatted @property');
            }
        }
    }

    /**
     * @param  string $return_block
     *
     * @throws DocblockParseException if an invalid string is found
     *
     * @return array<string>
     */
    public static function splitDocLine($return_block)
    {
        $brackets = '';

        $type = '';

        $expects_callable_return = false;

        $return_block = preg_replace('/[ \t]+/', ' ', $return_block);

        for ($i = 0, $l = strlen($return_block); $i < $l; ++$i) {
            $char = $return_block[$i];
            $next_char = $i < $l - 1 ? $return_block[$i + 1] : null;

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

                if ($next_char === ':') {
                    ++$i;
                    $type .= ':';
                    $expects_callable_return = true;
                    continue;
                }

                if ($expects_callable_return) {
                    $expects_callable_return = false;
                    continue;
                }

                $remaining = trim(substr($return_block, $i + 1));

                if ($remaining) {
                    return array_merge([$type], explode(' ', $remaining));
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
     * @param  bool    $preserve_format
     *
     * @return array Array of the main comment and specials
     * @psalm-return array{description:string, specials:array<string, array<int, string>>}
     */
    public static function parseDocComment($docblock, $line_number = null, $preserve_format = false)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^[ \t]*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $line_map = [];

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = rtrim($old_last_line) . ($preserve_format ? "\n" . $line : ' ' . trim($line));

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

        $special = [];

        if ($preserve_format) {
            foreach ($lines as $m => $line) {
                if (preg_match('/^\s?@([\w\-:]+)[\t ]*(.*)$/sm', $line, $matches)) {
                    /** @var string[] $matches */
                    list($full_match, $type, $data) = $matches;

                    $docblock = str_replace($full_match, '', $docblock);

                    if (empty($special[$type])) {
                        $special[$type] = [];
                    }

                    $line_number = $line_map && isset($line_map[$full_match]) ? $line_map[$full_match] : (int)$m;

                    $special[$type][$line_number] = rtrim($data);
                }
            }
        } else {
            $docblock = implode("\n", $lines);

            // Parse @specials.
            if (preg_match_all('/^\s?@([\w\-:]+)[\t ]*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER)) {
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
     * @return string
     */
    public static function renderDocComment(array $parsed_doc_comment, $left_padding)
    {
        $doc_comment_text = '/**' . "\n";

        $description_lines = null;

        $trimmed_description = trim($parsed_doc_comment['description']);

        if (!empty($trimmed_description)) {
            $description_lines = explode("\n", $parsed_doc_comment['description']);

            foreach ($description_lines as $line) {
                $doc_comment_text .= $left_padding . ' *' . (trim($line) ? ' ' . $line : '') . "\n";
            }
        }

        if ($description_lines && $parsed_doc_comment['specials']) {
            $doc_comment_text .= $left_padding . ' *' . "\n";
        }

        if ($parsed_doc_comment['specials']) {
            $last_type = null;

            foreach ($parsed_doc_comment['specials'] as $type => $lines) {
                if ($last_type !== null && $last_type !== 'psalm-return') {
                    $doc_comment_text .= $left_padding . ' *' . "\n";
                }

                foreach ($lines as $line) {
                    $doc_comment_text .= $left_padding . ' * @' . $type . ' '
                        . str_replace("\n", "\n" . $left_padding . ' *', $line) . "\n";
                }

                $last_type = $type;
            }
        }

        $doc_comment_text .= $left_padding . ' */' . "\n" . $left_padding;

        return $doc_comment_text;
    }
}
