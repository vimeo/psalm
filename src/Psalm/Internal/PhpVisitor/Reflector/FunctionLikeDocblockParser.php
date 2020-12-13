<?php
namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Scanner\FunctionDocblockComment;
use Psalm\Internal\Scanner\ParsedDocblock;
use function array_unique;
use function trim;
use function substr_count;
use function strlen;
use function preg_replace;
use function str_replace;
use function preg_match;
use function count;
use function reset;
use function preg_split;
use function array_shift;
use function implode;
use function substr;
use function strpos;
use function strtolower;
use function in_array;
use function explode;

class FunctionLikeDocblockParser
{
    /**
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function parse(PhpParser\Comment\Doc $comment): FunctionDocblockComment
    {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        $comment_text = $comment->getText();

        $info = new FunctionDocblockComment();

        self::checkDuplicatedTags($parsed_docblock);

        if (isset($parsed_docblock->combined_tags['return'])) {
            self::extractReturnType(
                $comment,
                $parsed_docblock->combined_tags['return'],
                $info
            );
        }

        if (isset($parsed_docblock->combined_tags['param'])) {
            foreach ($parsed_docblock->combined_tags['param'] as $offset => $param) {
                $line_parts = CommentAnalyzer::splitDocLine($param);

                if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                    continue;
                }

                if (count($line_parts) > 1) {
                    if (preg_match('/^&?(\.\.\.)?&?\$[A-Za-z0-9_]+,?$/', $line_parts[1])
                        && $line_parts[0][0] !== '{'
                    ) {
                        $line_parts[1] = str_replace('&', '', $line_parts[1]);

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $start = $offset + $comment->getStartFilePos();
                        $end = $start + strlen($line_parts[0]);

                        $line_parts[0] = CommentAnalyzer::sanitizeDocblockType($line_parts[0]);

                        if ($line_parts[0] === ''
                            || ($line_parts[0][0] === '$'
                                && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                        ) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $info->params[] = [
                            'name' => trim($line_parts[1]),
                            'type' => $line_parts[0],
                            'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                            'start' => $start,
                            'end' => $end,
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($parsed_docblock->combined_tags['param-out'])) {
            foreach ($parsed_docblock->combined_tags['param-out'] as $offset => $param) {
                $line_parts = CommentAnalyzer::splitDocLine($param);

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

                        $line_parts[0] = str_replace("\n", '', preg_replace('@^[ \t]*\*@m', '', $line_parts[0]));

                        if ($line_parts[0] === ''
                            || ($line_parts[0][0] === '$'
                                && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                        ) {
                            throw new IncorrectDocblockException('Misplaced variable');
                        }

                        $line_parts[1] = preg_replace('/,$/', '', $line_parts[1]);

                        $info->params_out[] = [
                            'name' => trim($line_parts[1]),
                            'type' => str_replace("\n", '', $line_parts[0]),
                            'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-self-out'])) {
            foreach ($parsed_docblock->tags['psalm-self-out'] as $offset => $param) {
                $line_parts = CommentAnalyzer::splitDocLine($param);

                if (count($line_parts) > 0) {
                    $line_parts[0] = str_replace("\n", '', preg_replace('@^[ \t]*\*@m', '', $line_parts[0]));

                    $info->self_out = [
                        'type' => str_replace("\n", '', $line_parts[0]),
                        'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                    ];
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-flow'])) {
            foreach ($parsed_docblock->tags['psalm-flow'] as $param) {
                $info->flows[] = trim($param);
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-sink'])) {
            foreach ($parsed_docblock->tags['psalm-taint-sink'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if (count($param_parts) === 2) {
                    $info->taint_sink_params[] = ['name' => $param_parts[1], 'taint' => $param_parts[0]];
                }
            }
        }

        // support for MediaWiki taint plugin
        if (isset($parsed_docblock->tags['param-taint'])) {
            foreach ($parsed_docblock->tags['param-taint'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if (count($param_parts) === 2) {
                    $taint_type = $param_parts[1];

                    if (substr($taint_type, 0, 5) === 'exec_') {
                        $taint_type = substr($taint_type, 5);

                        if ($taint_type === 'tainted') {
                            $taint_type = 'input';
                        }

                        if ($taint_type === 'misc') {
                            $taint_type = 'text';
                        }

                        $info->taint_sink_params[] = ['name' => $param_parts[0], 'taint' => $taint_type];
                    }
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-source'])) {
            foreach ($parsed_docblock->tags['psalm-taint-source'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if ($param_parts[0]) {
                    $info->taint_source_types[] = $param_parts[0];
                }
            }
        } elseif (isset($parsed_docblock->tags['return-taint'])) {
            // support for MediaWiki taint plugin
            foreach ($parsed_docblock->tags['return-taint'] as $param) {
                $param_parts = preg_split('/\s+/', trim($param));

                if ($param_parts[0]) {
                    if ($param_parts[0] === 'tainted') {
                        $param_parts[0] = 'input';
                    }

                    if ($param_parts[0] === 'misc') {
                        $param_parts[0] = 'text';
                    }

                    if ($param_parts[0] !== 'none') {
                        $info->taint_source_types[] = $param_parts[0];
                    }
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-unescape'])) {
            foreach ($parsed_docblock->tags['psalm-taint-unescape'] as $param) {
                $param = trim($param);
                $info->added_taints[] = $param;
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-escape'])) {
            foreach ($parsed_docblock->tags['psalm-taint-escape'] as $param) {
                $param = trim($param);
                if ($param[0] === '(') {
                    $line_parts = CommentAnalyzer::splitDocLine($param);

                    $info->removed_taints[] = CommentAnalyzer::sanitizeDocblockType($line_parts[0]);
                } else {
                    $info->removed_taints[] = explode(' ', $param)[0];
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert-untainted'])) {
            foreach ($parsed_docblock->tags['psalm-assert-untainted'] as $param) {
                $param = trim($param);

                $info->assert_untainted_params[] = ['name' => $param];
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-specialize'])) {
            $info->specialize_call = true;
        }

        if (isset($parsed_docblock->tags['global'])) {
            foreach ($parsed_docblock->tags['global'] as $offset => $global) {
                $line_parts = CommentAnalyzer::splitDocLine($global);

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
                            'line_number' => $comment->getStartLine() + substr_count($comment_text, "\n", 0, $offset),
                        ];
                    }
                } else {
                    throw new DocblockParseException('Badly-formatted @param');
                }
            }
        }

        if (isset($parsed_docblock->tags['since'])) {
            $since = trim(reset($parsed_docblock->tags['since']));
            if (preg_match('/^[4578]\.\d(\.\d+)?$/', $since)) {
                $since_parts = explode('.', $since);

                $info->since_php_major_version = (int)$since_parts[0];
                $info->since_php_minor_version = (int)$since_parts[1];
            }
        }

        if (isset($parsed_docblock->tags['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($parsed_docblock->tags['internal'])) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['psalm-internal'])) {
            $psalm_internal = reset($parsed_docblock->tags['psalm-internal']);
            if ($psalm_internal) {
                $info->psalm_internal = $psalm_internal;
            } else {
                throw new DocblockParseException('@psalm-internal annotation used without specifying namespace');
            }
            $info->psalm_internal = reset($parsed_docblock->tags['psalm-internal']);
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['psalm-suppress'])) {
            foreach ($parsed_docblock->tags['psalm-suppress'] as $offset => $suppress_entry) {
                foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $suppressed_issue) {
                    $info->suppressed_issues[$issue_offset + $offset + $comment->getStartFilePos()] = $suppressed_issue;
                }
            }
        }

        if (isset($parsed_docblock->tags['throws'])) {
            foreach ($parsed_docblock->tags['throws'] as $offset => $throws_entry) {
                $throws_class = preg_split('/[\s]+/', $throws_entry)[0];

                if (!$throws_class) {
                    throw new IncorrectDocblockException('Unexpectedly empty @throws');
                }

                $info->throws[] = [
                    $throws_class,
                    $offset + $comment->getStartFilePos(),
                    $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset)
                ];
            }
        }

        if (strpos(strtolower($parsed_docblock->description), '@inheritdoc') !== false
            || isset($parsed_docblock->tags['inheritdoc'])
            || isset($parsed_docblock->tags['inheritDoc'])
        ) {
            $info->inheritdoc = true;
        }

        if (isset($parsed_docblock->combined_tags['template'])) {
            foreach ($parsed_docblock->combined_tags['template'] as $template_line) {
                $template_type = preg_split('/[\s]+/', preg_replace('@^[ \t]*\*@m', '', $template_line));

                $template_name = array_shift($template_type);

                if (!$template_name) {
                    throw new IncorrectDocblockException('Empty @template tag');
                }

                if (count($template_type) > 1
                    && in_array(strtolower($template_type[0]), ['as', 'super', 'of'], true)
                ) {
                    $template_modifier = strtolower(array_shift($template_type));
                    $info->templates[] = [
                        $template_name,
                        $template_modifier,
                        implode(' ', $template_type),
                        false
                    ];
                } else {
                    $info->templates[] = [$template_name, null, null, false];
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert'])) {
            foreach ($parsed_docblock->tags['psalm-assert'] as $assertion) {
                $line_parts = CommentAnalyzer::splitDocLine($assertion);

                if (count($line_parts) < 2 || strpos($line_parts[1], '$') === false) {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $line_parts[0] = CommentAnalyzer::sanitizeDocblockType($line_parts[0]);

                $info->assertions[] = [
                    'type' => $line_parts[0],
                    'param_name' => $line_parts[1][0] === '$' ? substr($line_parts[1], 1) : $line_parts[1],
                ];
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert-if-true'])) {
            foreach ($parsed_docblock->tags['psalm-assert-if-true'] as $assertion) {
                $line_parts = CommentAnalyzer::splitDocLine($assertion);

                if (count($line_parts) < 2 || strpos($line_parts[1], '$') === false) {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->if_true_assertions[] = [
                    'type' => $line_parts[0],
                    'param_name' => $line_parts[1][0] === '$' ? substr($line_parts[1], 1) : $line_parts[1],
                ];
            }
        }

        if (isset($parsed_docblock->tags['psalm-assert-if-false'])) {
            foreach ($parsed_docblock->tags['psalm-assert-if-false'] as $assertion) {
                $line_parts = CommentAnalyzer::splitDocLine($assertion);

                if (count($line_parts) < 2 || strpos($line_parts[1], '$') === false) {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $info->if_false_assertions[] = [
                    'type' => $line_parts[0],
                    'param_name' => $line_parts[1][0] === '$' ? substr($line_parts[1], 1) : $line_parts[1],
                ];
            }
        }

        $info->variadic = isset($parsed_docblock->tags['psalm-variadic']);
        $info->pure = isset($parsed_docblock->tags['psalm-pure'])
            || isset($parsed_docblock->tags['pure']);

        if (isset($parsed_docblock->tags['psalm-mutation-free'])) {
            $info->mutation_free = true;
        }

        if (isset($parsed_docblock->tags['psalm-external-mutation-free'])) {
            $info->external_mutation_free = true;
        }

        if (isset($parsed_docblock->tags['no-named-arguments'])) {
            $info->no_named_args = true;
        }

        $info->ignore_nullable_return = isset($parsed_docblock->tags['psalm-ignore-nullable-return']);
        $info->ignore_falsable_return = isset($parsed_docblock->tags['psalm-ignore-falsable-return']);
        $info->stub_override = isset($parsed_docblock->tags['psalm-stub-override']);

        return $info;
    }

    /**
     * @param array<int, string> $return_specials
     */
    private static function extractReturnType(
        PhpParser\Comment\Doc $comment,
        array $return_specials,
        FunctionDocblockComment $info
    ): void {
        foreach ($return_specials as $offset => $return_block) {
            $return_lines = explode("\n", $return_block);

            if (!trim($return_lines[0])) {
                return;
            }

            $return_block = trim($return_block);

            if (!$return_block) {
                return;
            }

            $line_parts = CommentAnalyzer::splitDocLine($return_block);

            if ($line_parts[0][0] !== '{') {
                if ($line_parts[0][0] === '$' && !preg_match('/^\$this(\||$)/', $line_parts[0])) {
                    throw new IncorrectDocblockException('Misplaced variable');
                }

                $start = $offset + $comment->getStartFilePos();
                $end = $start + strlen($line_parts[0]);

                $line_parts[0] = CommentAnalyzer::sanitizeDocblockType($line_parts[0]);

                $info->return_type = array_shift($line_parts);
                $info->return_type_description = $line_parts ? implode(' ', $line_parts) : null;

                $info->return_type_line_number
                    = $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $offset);
                $info->return_type_start = $start;
                $info->return_type_end = $end;
            } else {
                throw new DocblockParseException('Badly-formatted @return type');
            }

            break;
        }
    }

    /**
     * @throws DocblockParseException if a duplicate is found
     */
    private static function checkDuplicatedTags(ParsedDocblock $parsed_docblock): void
    {
        if (count($parsed_docblock->tags['return'] ?? []) > 1
            || count($parsed_docblock->tags['psalm-return'] ?? []) > 1
            || count($parsed_docblock->tags['phpstan-return'] ?? []) > 1
        ) {
            throw new DocblockParseException('Found duplicated @return or prefixed @return tag');
        }

        self::checkDuplicatedParams($parsed_docblock->tags['param'] ?? []);
        self::checkDuplicatedParams($parsed_docblock->tags['psalm-param'] ?? []);
        self::checkDuplicatedParams($parsed_docblock->tags['phpstan-param'] ?? []);
    }

    /**
     * @param array<int, string> $param
     *
     *
     * @throws DocblockParseException  if a duplicate is found
     */
    private static function checkDuplicatedParams(array $param): void
    {
        $list_names = self::extractAllParamNames($param);

        if (count($list_names) !== count(array_unique($list_names))) {
            throw new DocblockParseException('Found duplicated @param or prefixed @param tag');
        }
    }

    /**
     * @param array<int, string> $lines
     *
     * @return list<string>
     *
     * @psalm-pure
     */
    private static function extractAllParamNames(array $lines): array
    {
        $names = [];

        foreach ($lines as $line) {
            $split_by_dollar = explode('$', $line, 2);
            if (count($split_by_dollar) > 1) {
                $split_by_space = explode(' ', $split_by_dollar[1], 2);
                $names[] = $split_by_space[0];
            }
        }

        return $names;
    }
}
