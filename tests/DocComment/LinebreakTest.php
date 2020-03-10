<?php
namespace Psalm\Tests\Config;

use Psalm\DocComment;
use function str_replace;
use function current;

class LinebreakTest extends \Psalm\Tests\TestCase
{
    public function testParameterIsRecognizedWithoutCarriageReturn(): void
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

        $doc = new \PhpParser\Comment\Doc($comment);
        $return = DocComment::parsePreservingLength($doc);

        $this->assertSame('string|null $bla', current($return['specials']['param']));
    }

    public function testCarriageReturnIsIgnoredInParameters(): void
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

    public function testDescriptionIsRecognizedWithoutCarriageReturn(): void
    {
        $description = <<<DESC
This is the description with
* some breaks
*
* and even empty lines in between
*
DESC;

        $comment = <<<COMMENT
/**
 $description
 * @param string|null \$bla
 */
COMMENT;

        $doc = new \PhpParser\Comment\Doc($comment);
        $return = DocComment::parsePreservingLength($doc);

        $this->assertSame($description, $return['description']);
    }

    public function testCarriageReturnIsIgnoredInDescription(): void
    {
        $description = <<<DESC
This is the description with
* some breaks
*
* and even empty lines in between
*
DESC;

        $comment = <<<COMMENT
/**
 $description
 * @param string|null \$bla
 */
COMMENT;

        $doc = new \PhpParser\Comment\Doc(str_replace("\n", "\r\n", $comment));
        $return = DocComment::parsePreservingLength($doc);

        $this->assertSame($description, $return['description']);
    }
}
