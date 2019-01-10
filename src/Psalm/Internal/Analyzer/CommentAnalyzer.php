<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Internal\Scanner\ClassLikeDocblockComment;
use Psalm\Internal\Scanner\FunctionDocblockComment;
use Psalm\Internal\Scanner\VarDocblockComment;
use Psalm\Internal\Type\ParseTree;
use Psalm\Type;

/**
 * @internal
 */
class CommentAnalyzer
{
    const TYPE_REGEX = '(\??\\\?[\(\)A-Za-z0-9_&\<\.=,\>\[\]\-\{\}:|?\\\\]*|\$[a-zA-Z_0-9_]+)';

    /**
     * @param  string           $comment
     * @param  Aliases          $aliases
     * @param  array<string, array{Type\Union, ?string}>|null   $template_type_map
     * @param  int|null         $var_line_number
     * @param  int|null         $came_from_line_number what line number in $source that $comment came from
     * @param  array<string, array<int, string>> $type_aliases
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
        array $template_type_map = null,
        $var_line_number = null,
        $came_from_line_number = null,
        array $type_aliases = null
    ) {
        $var_id = null;

        $var_type_tokens = null;
        $original_type = null;

        $var_comments = [];
        $comments = DocComment::parse($comment, $var_line_number);

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
                        $var_type_tokens = Type::fixUpLocalType(
                            $line_parts[0],
                            $aliases,
                            $template_type_map,
                            $type_aliases
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

                if (!$var_type_tokens || !$original_type) {
                    continue;
                }

                try {
                    $defined_type = Type::parseTokens($var_type_tokens, false, $template_type_map ?: []);
                } catch (TypeParseTreeException $e) {
                    if (is_int($came_from_line_number)) {
                        throw new DocblockParseException(
                            implode('', $var_type_tokens) .
                            ' is not a valid type' .
                            ' (from ' .
                            $source->getFilePath() .
                            ':' .
                            $came_from_line_number .
                            ')'
                        );
                    }

                    throw new DocblockParseException(implode('', $var_type_tokens) . ' is not a valid type');
                }

                $defined_type->setFromDocblock();

                $var_comment = new VarDocblockComment();
                $var_comment->type = $defined_type;
                $var_comment->original_type = $original_type;
                $var_comment->var_id = $var_id;
                $var_comment->line_number = $var_line_number;
                $var_comment->deprecated = isset($comments['specials']['deprecated']);
                $var_comment->internal = isset($comments['specials']['internal']);

                $var_comments[] = $var_comment;
            }
        }

        return $var_comments;
    }

    /**
     * @param  string           $comment
     * @param  Aliases          $aliases
     * @param  array<string, array<int, string>> $type_aliases
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return array<string, array<int, string>>
     */
    public static function getTypeAliasesFromComment(
        $comment,
        Aliases $aliases,
        array $type_aliases = null
    ) {
        $comments = DocComment::parse($comment);

        if (!isset($comments['specials']['psalm-type'])) {
            return [];
        }

        return self::getTypeAliasesFromCommentLines(
            $comments['specials']['psalm-type'],
            $aliases,
            $type_aliases
        );
    }

    /**
     * @param  array<string>    $type_alias_comment_lines
     * @param  Aliases          $aliases
     * @param  array<string, array<int, string>> $type_aliases
     *
     * @throws DocblockParseException if there was a problem parsing the docblock
     *
     * @return array<string, array<int, string>>
     */
    private static function getTypeAliasesFromCommentLines(
        array $type_alias_comment_lines,
        Aliases $aliases,
        array $type_aliases = null
    ) {
        $type_alias_tokens = [];

        foreach ($type_alias_comment_lines as $var_line) {
            $var_line = trim($var_line);

            if (!$var_line) {
                continue;
            }

            $var_line = preg_replace('/[ \t]+/', ' ', $var_line);

            $var_line_parts = preg_split('/( |=)/', $var_line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            if (!$var_line_parts) {
                continue;
            }

            $type_alias = array_shift($var_line_parts);

            if (!isset($var_line_parts[0])) {
                continue;
            }

            if ($var_line_parts[0] === ' ') {
                array_shift($var_line_parts);
            }

            if ($var_line_parts[0] === '=') {
                array_shift($var_line_parts);
            }

            if (!isset($var_line_parts[0])) {
                continue;
            }

            if ($var_line_parts[0] === ' ') {
                array_shift($var_line_parts);
            }

            $type_string = implode('', $var_line_parts);

            try {
                $type_tokens = Type::fixUpLocalType(
                    $type_string,
                    $aliases,
                    null,
                    $type_alias_tokens + $type_aliases
                );
            } catch (TypeParseTreeException $e) {
                throw new DocblockParseException($type_string . ' is not a valid type');
            }

            $type_alias_tokens[$type_alias] = $type_tokens;
        }

        return $type_alias_tokens;
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
        $comments = DocComment::parse($comment, $line_number);

        $info = new FunctionDocblockComment();

        if (isset($comments['specials']['return']) || isset($comments['specials']['psalm-return'])) {
            /** @var array<int, string> */
            $return_specials = isset($comments['specials']['psalm-return'])
                ? $comments['specials']['psalm-return']
                : $comments['specials']['return'];

            self::extractReturnType((string) reset($return_specials), array_keys($return_specials)[0], $info);
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
                    if (!preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && $line_parts[0][0] !== '{'
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

        if (isset($comments['specials']['global'])) {
            foreach ($comments['specials']['global'] as $line_number => $global) {
                try {
                    $line_parts = self::splitDocLine($global);
                } catch (DocblockParseException $e) {
                    throw $e;
                }

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    continue;
                }

                if (count($line_parts) > 1) {
                    if (!preg_match('/\[[^\]]+\]/', $line_parts[0])
                        && preg_match('/^(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && $line_parts[0][0] !== '{'
                    ) {
                        if ($line_parts[1][0] === '&') {
                            $line_parts[1] = substr($line_parts[1], 1);
                        }

                        if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->globals[] = [
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

        if (isset($comments['specials']['internal'])) {
            $info->internal = true;
        }

        if (isset($comments['specials']['psalm-suppress'])) {
            foreach ($comments['specials']['psalm-suppress'] as $suppress_entry) {
                $info->suppress[] = preg_split('/[\s]+/', $suppress_entry)[0];
            }
        }

        if (isset($comments['specials']['throws'])) {
            foreach ($comments['specials']['throws'] as $throws_entry) {
                $throws_class = preg_split('/[\s]+/', $throws_entry)[0];

                if (!$throws_class) {
                    throw new IncorrectDocblockException('Unexpectedly empty @throws');
                }

                $info->throws[] = $throws_class;
            }
        }

        if (strpos(strtolower($comments['description']), '@inheritdoc') !== false) {
            $info->inheritdoc = true;
        }

        if (isset($comments['specials']['template']) || isset($comments['specials']['psalm-template'])) {
            $all_templates = (isset($comments['specials']['template']) ? $comments['specials']['template'] : [])
                + (isset($comments['specials']['psalm-template']) ? $comments['specials']['psalm-template'] : []);

            foreach ($all_templates as $template_line) {
                $template_type = preg_split('/[\s]+/', $template_line);

                if (count($template_type) > 2 && in_array(strtolower($template_type[1]), ['as', 'super'], true)) {
                    $info->templates[] = [
                        $template_type[0],
                        strtolower($template_type[1]), $template_type[2]
                    ];
                } else {
                    $info->templates[] = [$template_type[0]];
                }
            }
        }

        if (isset($comments['specials']['template-typeof'])) {
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

        if (isset($comments['specials']['psalm-assert'])) {
            foreach ($comments['specials']['psalm-assert'] as $assertion) {
                $assertion_parts = preg_split('/[\s]+/', $assertion);

                if (count($assertion_parts) < 2 || $assertion_parts[1][0] !== '$') {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->assertions[] = [
                    'type' => $assertion_parts[0],
                    'param_name' => substr($assertion_parts[1], 1),
                ];
            }
        }

        if (isset($comments['specials']['psalm-assert-if-true'])) {
            foreach ($comments['specials']['psalm-assert-if-true'] as $assertion) {
                $assertion_parts = preg_split('/[\s]+/', $assertion);

                if (count($assertion_parts) < 2 || $assertion_parts[1][0] !== '$') {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->if_true_assertions[] = [
                    'type' => $assertion_parts[0],
                    'param_name' => substr($assertion_parts[1], 1),
                ];
            }
        }

        if (isset($comments['specials']['psalm-assert-if-false'])) {
            foreach ($comments['specials']['psalm-assert-if-false'] as $assertion) {
                $assertion_parts = preg_split('/[\s]+/', $assertion);

                if (count($assertion_parts) < 2 || $assertion_parts[1][0] !== '$') {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->if_false_assertions[] = [
                    'type' => $assertion_parts[0],
                    'param_name' => substr($assertion_parts[1], 1),
                ];
            }
        }

        $info->variadic = isset($comments['specials']['psalm-variadic']);
        $info->ignore_nullable_return = isset($comments['specials']['psalm-ignore-nullable-return']);
        $info->ignore_falsable_return = isset($comments['specials']['psalm-ignore-falsable-return']);

        return $info;
    }

    /**
     * @return void
     */
    private static function extractReturnType(string $return_block, int $line_number, FunctionDocblockComment $info)
    {
        $return_lines = explode("\n", $return_block);

        if (!trim($return_lines[0])) {
            return;
        }

        $return_block = trim($return_block);

        if (!$return_block) {
            return;
        }

        try {
            $line_parts = self::splitDocLine($return_block);
        } catch (DocblockParseException $e) {
            throw $e;
        }

        if (!preg_match('/\[[^\]]+\]/', $line_parts[0])
            && $line_parts[0][0] !== '{'
        ) {
            if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                throw new IncorrectDocblockException('Misplaced variable');
            }

            $info->return_type = array_shift($line_parts);
            $info->return_type_description = $line_parts ? implode(' ', $line_parts) : null;

            if ($line_number) {
                $info->return_type_line_number = $line_number;
            }
        } else {
            throw new DocblockParseException('Badly-formatted @return type');
        }
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
        $comments = DocComment::parse($comment, $line_number);

        $info = new ClassLikeDocblockComment();

        if (isset($comments['specials']['template'])) {
            foreach ($comments['specials']['template'] as $template_line) {
                $template_type = preg_split('/[\s]+/', $template_line);

                if (count($template_type) > 2 && in_array(strtolower($template_type[1]), ['as', 'super'], true)) {
                    $info->templates[] = [
                        $template_type[0],
                        strtolower($template_type[1]), $template_type[2]
                    ];
                } else {
                    $info->templates[] = [$template_type[0]];
                }
            }
        }

        if (isset($comments['specials']['template-extends'])) {
            foreach ($comments['specials']['template-extends'] as $template_line) {
                $info->template_extends[] = $template_line;
            }
        }

        if (isset($comments['specials']['template-implements'])) {
            foreach ($comments['specials']['template-implements'] as $template_line) {
                $info->template_extends[] = $template_line;
            }
        }

        if (isset($comments['specials']['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($comments['specials']['internal'])) {
            $info->internal = true;
        }

        if (isset($comments['specials']['psalm-seal-properties'])) {
            $info->sealed_properties = true;
        }

        if (isset($comments['specials']['psalm-seal-methods'])) {
            $info->sealed_methods = true;
        }

        if (isset($comments['specials']['psalm-override-property-visibility'])) {
            $info->override_property_visibility = true;
        }

        if (isset($comments['specials']['psalm-override-method-visibility'])) {
            $info->override_method_visibility = true;
        }

        if (isset($comments['specials']['psalm-suppress'])) {
            foreach ($comments['specials']['psalm-suppress'] as $suppress_entry) {
                $info->suppressed_issues[] = preg_split('/[\s]+/', $suppress_entry)[0];
            }
        }

        if (isset($comments['specials']['method'])) {
            foreach ($comments['specials']['method'] as $method_entry) {
                $method_entry = preg_replace('/[ \t]+/', ' ', trim($method_entry));

                $docblock_lines = [];

                if (!preg_match('/^([a-z_A-Z][a-z_0-9A-Z]+) *\(/', $method_entry, $matches)) {
                    $doc_line_parts = self::splitDocLine($method_entry);

                    $docblock_lines[] = '@return ' . array_shift($doc_line_parts);

                    $method_entry = implode(' ', $doc_line_parts);
                }

                $method_entry = trim(preg_replace('/\/\/.*/', '', $method_entry));

                $end_of_method_regex = '/(?<!array\()\) ?(\: ?(\??[\\\\a-zA-Z0-9_]+))?/';

                if (preg_match($end_of_method_regex, $method_entry, $matches, PREG_OFFSET_CAPTURE)) {
                    $method_entry = substr($method_entry, 0, (int) $matches[0][1] + strlen((string) $matches[0][0]));
                }

                $method_entry = str_replace([', ', '( '], [',', '('], $method_entry);
                $method_entry = preg_replace('/ (?!(\$|\.\.\.|&))/', '', trim($method_entry));

                try {
                    $method_tree = ParseTree::createFromTokens(Type::tokenize($method_entry, false));
                } catch (TypeParseTreeException $e) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                if (!$method_tree instanceof ParseTree\MethodWithReturnTypeTree
                    && !$method_tree instanceof ParseTree\MethodTree) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                if ($method_tree instanceof ParseTree\MethodWithReturnTypeTree) {
                    $docblock_lines[] = '@return ' . Type::getTypeFromTree($method_tree->children[1]);
                    $method_tree = $method_tree->children[0];
                }

                if (!$method_tree instanceof ParseTree\MethodTree) {
                    throw new DocblockParseException($method_entry . ' is not a valid method');
                }

                $args = [];

                foreach ($method_tree->children as $method_tree_child) {
                    if (!$method_tree_child instanceof ParseTree\MethodParamTree) {
                        throw new DocblockParseException($method_entry . ' is not a valid method');
                    }

                    $args[] = ($method_tree_child->byref ? '&' : '')
                        . ($method_tree_child->variadic ? '...' : '')
                        . $method_tree_child->name
                        . ($method_tree_child->default != '' ? ' = ' . $method_tree_child->default : '');


                    if ($method_tree_child->children) {
                        $param_type = Type::getTypeFromTree($method_tree_child->children[0]);
                        $docblock_lines[] = '@param ' . $param_type . ' '
                            . ($method_tree_child->variadic ? '...' : '')
                            . $method_tree_child->name;
                    }
                }

                $function_string = 'function ' . $method_tree->value . '(' . implode(', ', $args) . ')';

                $function_docblock = $docblock_lines ? "/**\n * " . implode("\n * ", $docblock_lines) . "\n*/\n" : "";

                $php_string = '<?php class A { ' . $function_docblock . ' public ' . $function_string . '{} }';

                try {
                    $statements = \Psalm\Internal\Provider\StatementsProvider::parseStatements($php_string);
                } catch (\Exception $e) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                if (!$statements[0] instanceof \PhpParser\Node\Stmt\Class_
                    || !isset($statements[0]->stmts[0])
                    || !$statements[0]->stmts[0] instanceof \PhpParser\Node\Stmt\ClassMethod
                ) {
                    throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
                }

                $info->methods[] = $statements[0]->stmts[0];
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

        $quote_char = null;
        $escaped = false;

        for ($i = 0, $l = strlen($return_block); $i < $l; ++$i) {
            $char = $return_block[$i];
            $next_char = $i < $l - 1 ? $return_block[$i + 1] : null;

            if ($quote_char) {
                if ($char === $quote_char && $i > 1 && !$escaped) {
                    $quote_char = null;

                    $type .= $char;

                    continue;
                }

                if ($char === '\\' && !$escaped && ($next_char === $quote_char || $next_char === '\\')) {
                    $escaped = true;

                    $type .= $char;

                    continue;
                }

                $escaped = false;

                $type .= $char;

                continue;
            }

            if ($char === '"' || $char === '\'') {
                $quote_char = $char;

                $type .= $char;

                continue;
            }

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
            } elseif ($char === ' ') {
                if ($brackets) {
                    continue;
                }

                if ($next_char === '|') {
                    ++$i;
                    $type .= $next_char;
                    continue;
                }

                $last_char = $i > 0 ? $return_block[$i - 1] : null;

                if ($last_char === '|') {
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
}
