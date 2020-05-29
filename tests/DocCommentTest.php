<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\DocComment;
use Psalm\Internal\Scanner\ParsedDocblock;

class DocCommentTest extends BaseTestCase
{
    public function testNewLineIsAddedBetweenAnnotationsByDefault(): void
    {
        $docComment = new ParsedDocblock(
            '* some desc' . "\n*",
            [
                'param' =>
                    [
                        2 => 'string $bli',
                        3 => 'int $bla',
                    ],
                'throws' =>
                    [
                        0 => '\Exception',
                    ],
                'return' =>
                    [
                        0 => 'bool',
                    ],
            ]
        );

        $expectedDoc = '/**
 * some desc
 *
 * @param string $bli
 * @param int $bla
 *
 * @throws \Exception
 *
 * @return bool
 */
';

        $this->assertSame($expectedDoc, $docComment->render(''));
    }

    public function testNewLineIsNotAddedBetweenAnnotationsIfDisabled(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(false);

        $docComment = new ParsedDocblock(
            '* some desc' . "\n*",
            [
                'param' =>
                    [
                        2 => 'string $bli',
                        3 => 'int $bla',
                    ],
                'throws' =>
                    [
                        0 => '\Exception',
                    ],
                'return' =>
                    [
                        0 => 'bool',
                    ],
            ]
        );

        $expectedDoc = '/**
 * some desc
 *
 * @param string $bli
 * @param int $bla
 * @throws \Exception
 * @return bool
 */
';

        $this->assertSame($expectedDoc, $docComment->render(''));
    }

    public function testNewLineIsAddedBetweenAnnotationsIfEnabled(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(true);

        $docComment = new ParsedDocblock(
            '* some desc' . "\n*",
            [
                'param' =>
                    [
                        2 => 'string $bli',
                        3 => 'int $bla',
                    ],
                'throws' =>
                    [
                        0 => '\Exception',
                    ],
                'return' =>
                    [
                        0 => 'bool',
                    ],
            ]
        );

        $expectedDoc = '/**
 * some desc
 *
 * @param string $bli
 * @param int $bla
 *
 * @throws \Exception
 *
 * @return bool
 */
';

        $this->assertSame($expectedDoc, $docComment->render(''));
    }
}
