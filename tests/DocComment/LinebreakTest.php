<?php
namespace Psalm\Tests\Config;

use Psalm\DocComment;

class LinebreakTest extends \Psalm\Tests\TestCase
{
    public function testCarriageReturnIsIgnoredInParameters()
    {
        $comment = <<<COMMENT
/**
 * @param string|null \$bla
 * @return array{
 *          0: \Some\Weird\Space\A|null,
 *          1: null
 *          }
 */
COMMENT;

        $doc = new \PhpParser\Comment\Doc(str_replace("\n", "\r\n", $comment));
        $return = DocComment::parsePreservingLength($doc);

        $this->assertSame('string|null $bla', current($return['specials']['param']));
    }
}
