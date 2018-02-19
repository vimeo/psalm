<?php
namespace Psalm\Provider;

use PhpParser;

class StatementsProvider
{
    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var ParserCacheProvider
     */
    private $cache_provider;

    /**
     * @var PhpParser\Parser|null
     */
    protected static $parser;

    public function __construct(FileProvider $file_provider, ParserCacheProvider $cache_provider)
    {
        $this->file_provider = $file_provider;
        $this->cache_provider = $cache_provider;
    }

    /**
     * @param  string  $file_path
     * @param  bool    $debug_output
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public function getStatementsForFile($file_path, $debug_output = false)
    {
        $from_cache = false;

        $version = 'parsercache5';

        $file_contents = $this->file_provider->getContents($file_path);
        $modified_time = $this->file_provider->getModifiedTime($file_path);

        $file_content_hash = md5($version . $file_contents);
        $file_cache_key = $this->cache_provider->getParserCacheKey($file_path, $this->cache_provider->use_igbinary);

        $stmts = $this->cache_provider->loadStatementsFromCache(
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

        $this->cache_provider->saveStatementsToCache($file_cache_key, $file_content_hash, $stmts, $from_cache);

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
