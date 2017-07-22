<?php
namespace Psalm\Provider;

use PhpParser;

class StatementsProvider
{
    /** @var ?PhpParser\Parser */
    protected static $parser;

    /**
     * @param  string  $file_path
     * @param  FileProvider $file_provider
     * @param  CacheProvider $cache_provider
     * @param  bool    $debug_output
     *
     * @return array<int, \PhpParser\Node\Expr|\PhpParser\Node\Stmt>
     */
    public static function getStatementsForFile(
        $file_path,
        FileProvider $file_provider,
        CacheProvider $cache_provider,
        $debug_output = false
    ) {
        $stmts = [];

        $from_cache = false;

        $version = 'parsercache4';

        $file_contents = $file_provider->getContents($file_path);
        $modified_time = $file_provider->getModifiedTime($file_path);

        $file_content_hash = md5($version . $file_contents);
        $file_cache_key = $cache_provider->getParserCacheKey($file_path);

        $stmts = $cache_provider->loadStatementsFromCache(
            $file_path,
            $modified_time,
            $file_content_hash,
            $file_cache_key
        );

        if ($stmts === null) {
            if ($debug_output) {
                echo 'Parsing ' . $file_path . PHP_EOL;
            }

            $stmts = self::parseStatementsInFile($file_contents);
        } else {
            $from_cache = true;
        }

        $cache_provider->saveStatementsToCache($file_cache_key, $file_content_hash, $stmts, $from_cache);

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    /**
     * @param  string   $file_contents
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    private static function parseStatementsInFile($file_contents)
    {
        if (!self::$parser) {
            $lexer = version_compare(PHP_VERSION, '7.0.0dev', '>=')
                ? new PhpParser\Lexer([
                    'usedAttributes' => [
                        'comments', 'startLine', 'startFilePos', 'endFilePos',
                    ],
                ])
                : new PhpParser\Lexer\Emulative([
                    'usedAttributes' => [
                        'comments', 'startLine', 'startFilePos', 'endFilePos',
                    ],
                ]);

            self::$parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);
        }

        $error_handler = new \PhpParser\ErrorHandler\Collecting();

        /** @var array<int, \PhpParser\Node\Stmt> */
        $stmts = self::$parser->parse($file_contents, $error_handler);

        if (!$stmts && $error_handler->hasErrors()) {
            foreach ($error_handler->getErrors() as $error) {
                throw $error;
            }
        }

        return $stmts;
    }
}
