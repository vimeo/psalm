<?php

namespace CodeInspector;

class CommentChecker
{
    const TYPE_REGEX = '(\\\?[A-Za-z0-9\<\>\[\]|\\\]+[A-Za-z0-9\<\>\[\]]|\$[a-zA-Z_0-9\<\>\|\[\]]+)';

    /**
     * @param  string           $comment
     * @param  Context          $context
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
            $var_parts = array_filter(preg_split('/[\s\t]+/', $comments['specials']['var'][0]));

            if ($var_parts) {
                $type_in_comments = $var_parts[0];

                if ($type_in_comments[0] === strtoupper($type_in_comments[0])) {
                    $type_in_comments = ClassChecker::getAbsoluteClassFromString($type_in_comments, $source->getNamespace(), $source->getAliasedClasses());
                }

                // support PHPStorm-style docblocks like
                // @var Type $variable
                if (count($var_parts) > 1 && $var_parts[1][0] === '$') {
                    $type_in_comments_var_id = substr($var_parts[1], 1);
                }
            }
        }

        if (!$type_in_comments) {
            return null;
        }

        $defined_type = Type::parseString($type_in_comments);

        if ($context && $type_in_comments_var_id && $type_in_comments_var_id !== $var_id) {
            if (isset($context->vars_in_scope[$type_in_comments_var_id])) {
                $context->vars_in_scope[$type_in_comments_var_id] = $defined_type;
            }

            return null;
        }

        return $defined_type;
    }

    public static function extractDocblockInfo($comment)
    {
        $comments = StatementsChecker::parseDocComment($comment);

        $info = ['return_type' => null, 'params' => null];

        if (isset($comments['specials']['return'])) {
            $return_blocks = preg_split('/[\s]+/', $comments['specials']['return'][0]);

            if (preg_match('/^' . self::TYPE_REGEX . '$/', $return_blocks[0]) && !preg_match('/\[[^\]]+\]/', $return_blocks[0])) {
                $info['return_type'] = $return_blocks[0];
            }
        }

        if (isset($comments['specials']['param'])) {
            foreach ($comments['specials']['param'] as $param) {
                $param_blocks = preg_split('/[\s]+/', $param);

                if (count($param_blocks) > 1 &&
                    preg_match('/^' . self::TYPE_REGEX . '$/', $param_blocks[0]) &&
                    !preg_match('/\[[^\]]+\]/', $param_blocks[0]) &&
                    preg_match('/^\$[A-Za-z0-9_]+$/', $param_blocks[1])
                ) {
                    $info['params'] = ['name' => $param_blocks[1], 'type' => $param_blocks[0]];
                }
            }
        }

        return $info;

    }
}
