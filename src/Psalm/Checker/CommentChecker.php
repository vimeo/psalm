<?php
namespace Psalm\Checker;

use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\StatementsSource;
use Psalm\Type;

class CommentChecker
{
    const TYPE_REGEX =
        '(\\\?[A-Za-z0-9_\<,\>\[\]\-\{\}:|\\\]+[A-Za-z0-9_\<,\>\[\]-\{\}:]|\$[a-zA-Z_0-9_\<,\>\|\[\]-\{\}:]+)';

    /**
     * @param  string           $comment
     * @param  Context|null     $context
     * @param  StatementsSource $source
     * @param  string           $var_id
     * @return Type\Union|null
     * @throws DocblockParseException If there was a problem parsing the docblock.
     */
    public static function getTypeFromComment(
        $comment,
        Context $context = null,
        StatementsSource $source,
        $var_id = null
    ) {
        $type_in_comments_var_id = null;

        $type_in_comments = null;

        $comments = self::parseDocComment($comment);

        if ($comments && isset($comments['specials']['var'][0]) && trim((string)$comments['specials']['var'][0])) {
            try {
                $line_parts = self::splitDocLine(trim((string)$comments['specials']['var'][0]));
            } catch (DocblockParseException $e) {
                throw $e;
            }

            if ($line_parts && $line_parts[0]) {
                $type_in_comments = FunctionLikeChecker::fixUpLocalType(
                    $line_parts[0],
                    $source->getFullyQualifiedClass(),
                    $source->getNamespace(),
                    $source->getAliasedClasses()
                );

                // support PHPStorm-style docblocks like
                // @var Type $variable
                if (count($line_parts) > 1 && $line_parts[1][0] === '$') {
                    $type_in_comments_var_id = $line_parts[1];
                }
            }
        }

        if (!$type_in_comments) {
            return null;
        }

        $defined_type = Type::parseString($type_in_comments);

        if ($context && $type_in_comments_var_id && $type_in_comments_var_id !== $var_id) {
            $context->vars_in_scope[$type_in_comments_var_id] = $defined_type;

            return null;
        }

        return $defined_type;
    }

    /**
     * @param  string $comment
     * @return array
     * @psalm-return array{
     *  return_type: null|string,
     *  params: array<int, array{name:string, type:string}>,
     *  deprecated: bool,
     *  suppress: array<string>,
     *  variadic: boolean
     * }
     * @throws DocblockParseException If there was a problem parsing the docblock.
     */
    public static function extractDocblockInfo($comment)
    {
        $comments = self::parseDocComment($comment);

        $info = [
            'return_type' => null,
            'params' => [],
            'deprecated' => false,
            'suppress' => []
        ];

        if (isset($comments['specials']['return']) || isset($comments['specials']['psalm-return'])) {
            $return_block = trim(
                isset($comments['specials']['psalm-return'])
                    ? (string)$comments['specials']['psalm-return'][0]
                    : (string)$comments['specials']['return'][0]
            );

            try {
                $line_parts = self::splitDocLine($return_block);
            } catch (DocblockParseException $e) {
                throw $e;
            }

            if (preg_match('/^' . self::TYPE_REGEX . '$/', $line_parts[0])
                && !preg_match('/\[[^\]]+\]/', $line_parts[0])
                && !strpos($line_parts[0], '::')
            ) {
                $info['return_type'] = $line_parts[0];
            }
        }

        if (isset($comments['specials']['param'])) {
            foreach ($comments['specials']['param'] as $param) {
                try {
                    $line_parts = self::splitDocLine((string)$param);
                } catch (DocblockParseException $e) {
                    throw $e;
                }

                if (count($line_parts) > 1
                    && preg_match('/^' . self::TYPE_REGEX . '$/', $line_parts[0])
                    && !preg_match('/\[[^\]]+\]/', $line_parts[0])
                    && preg_match('/^&?\$[A-Za-z0-9_]+$/', $line_parts[1])
                    && !strpos($line_parts[0], '::')
                ) {
                    if ($line_parts[1][0] === '&') {
                        $line_parts[1] = substr($line_parts[1], 1);
                    }

                    $info['params'][] = ['name' => substr($line_parts[1], 1), 'type' => $line_parts[0]];
                }
            }
        }

        if (isset($comments['specials']['deprecated'])) {
            $info['deprecated'] = true;
        }

        if (isset($comments['specials']['psalm-suppress'])) {
            foreach ($comments['specials']['psalm-suppress'] as $suppress_entry) {
                $info['suppress'][] = preg_split('/[\s]+/', (string)$suppress_entry)[0];
            }
        }

        $info['variadic'] = isset($comments['specials']['psalm-variadic']);

        return $info;
    }

    /**
     * @param  string $return_block
     * @return array<string>
     * @throws DocblockParseException If an invalid string is found.
     */
    protected static function splitDocLine($return_block)
    {
        $brackets = '';

        $type = '';

        for ($i = 0; $i < strlen($return_block); $i++) {
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
     * @return array Array of the main comment and specials
     */
    public static function parseDocComment($docblock)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^\s*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);
        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $lines[$last] = rtrim($lines[$last]).' '.trim($line);
                unset($lines[$k]);
            }
        }

        $docblock = implode("\n", $lines);

        $special = [];

        // Parse @specials.
        $matches = [];
        $have_specials = preg_match_all('/^\s?@([\w\-:]+)\s*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER);
        if ($have_specials) {
            $docblock = preg_replace('/^\s?@([\w\-:]+)\s*([^\n]*)/m', '', $docblock);
            foreach ($matches as $match) {
                list($_, $type, $data) = $match;

                if (empty($special[$type])) {
                    $special[$type] = array();
                }

                $special[$type][] = $data;
            }
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0; $ii < strlen($line); $ii++) {
                if ($line[$ii] != ' ') {
                    break;
                }
                $indent++;
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
            'specials' => $special
        ];
    }
}
