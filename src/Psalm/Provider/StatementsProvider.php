<?php
namespace Psalm\Provider;

use PhpParser;
use Psalm\Checker\ProjectChecker;

class StatementsProvider
{
    /**
     * @param  string  $file_path
     * @param  string  $file_contents
     * @param  bool    $debug_output
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public static function getStatementsForFile($file_path, $file_contents, $modified_time, $debug_output = false)
    {
        $stmts = [];

        $from_cache = false;

        $version = 'parsercache4';

        $file_content_hash = md5($version . $file_contents);
        $file_cache_key = CacheProvider::getParserCacheKey($file_path);

        $stmts = CacheProvider::loadStatementsFromCache(
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

        CacheProvider::saveStatementsToCache($file_cache_key, $file_content_hash, $stmts, $from_cache);

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
        $lexer = new PhpParser\Lexer([
            'usedAttributes' => [
                'comments', 'startLine', 'startFilePos', 'endFilePos',
            ],
        ]);

        $parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);

        $error_handler = new \PhpParser\ErrorHandler\Collecting();

        /** @var array<int, \PhpParser\Node\Stmt> */
        $stmts = $parser->parse($file_contents, $error_handler);

        if (!$stmts && $error_handler->hasErrors()) {
            foreach ($error_handler->getErrors() as $error) {
                throw $error;
            }
        }

        return $stmts;
    }
}
