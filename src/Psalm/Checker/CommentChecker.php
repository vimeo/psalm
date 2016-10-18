<?php

namespace Psalm\Checker;

use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class CommentChecker
{
    const TYPE_REGEX = '(\\\?[A-Za-z0-9_\<,\>\[\]\-\{\}:|\\\]+[A-Za-z0-9_\<,\>\[\]-\{\}:]|\$[a-zA-Z_0-9_\<,\>\|\[\]-\{\}:]+)';

    /**
     * @param  string           $comment
     * @param  Context|null     $context
     * @param  StatementsSource $source
     * @param  string           $var_id
     * @return Type\Union|null
     */
    public static function getTypeFromComment($comment, Context $context = null, StatementsSource $source, $var_id = null)
    {
        $type_in_comments_var_id = null;

        $type_in_comments = null;

        $comments = StatementsChecker::parseDocComment($comment);

        if ($comments && isset($comments['specials']['var'][0])) {
            $var_parts = array_filter(preg_split('/[\s\t]+/', (string)$comments['specials']['var'][0]));

            if ($var_parts) {
                $type_in_comments = FunctionLikeChecker::fixUpLocalType($var_parts[0], $source->getAbsoluteClass(), $source->getNamespace(), $source->getAliasedClasses());

                // support PHPStorm-style docblocks like
                // @var Type $variable
                if (count($var_parts) > 1 && $var_parts[1][0] === '$') {
                    $type_in_comments_var_id = $var_parts[1];
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
     * @psalm-return object-like{return_type:null|string,params:array<object-like{name:string,type:string},deprecated:bool,suppress:array<string>,variadic:boolean}
     */
    public static function extractDocblockInfo($comment)
    {
        $comments = StatementsChecker::parseDocComment($comment);

        $info = ['return_type' => null, 'params' => [], 'deprecated' => false, 'suppress' => []];

        if (isset($comments['specials']['return']) || isset($comments['specials']['psalm-return'])) {
            $return_blocks = preg_split(
                '/[\s]+/',
                isset($comments['specials']['psalm-return'])
                    ? (string)$comments['specials']['psalm-return'][0]
                    : (string)$comments['specials']['return'][0]
            );

            if (preg_match('/^' . self::TYPE_REGEX . '$/', $return_blocks[0])
                && !preg_match('/\[[^\]]+\]/', $return_blocks[0])
                && !strpos($return_blocks[0], '::')) {
                $info['return_type'] = $return_blocks[0];
            }
        }

        if (isset($comments['specials']['param'])) {
            foreach ($comments['specials']['param'] as $param) {
                $param_blocks = preg_split('/[\s]+/', (string)$param);

                if (count($param_blocks) > 1
                    && preg_match('/^' . self::TYPE_REGEX . '$/', $param_blocks[0])
                    && !preg_match('/\[[^\]]+\]/', $param_blocks[0])
                    && preg_match('/^&?\$[A-Za-z0-9_]+$/', $param_blocks[1])
                    && !strpos($param_blocks[0], '::')
                ) {
                    if ($param_blocks[1][0] === '&') {
                        $param_blocks[1] = substr($param_blocks[1], 1);
                    }

                    $info['params'][] = ['name' => substr($param_blocks[1], 1), 'type' => $param_blocks[0]];
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
}
