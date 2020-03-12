<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\DocComment;

class DocCommentTest extends BaseTestCase
{
    public function testNewLineIsAddedInDocBlockBeforeReturnByDefault(): void
    {
        $docComment = [
            'description' => 'some desc',
            'specials' =>
                [
                    'param' =>
                        [
                            3 => 'int $bla',
                        ],
                    'return' =>
                        [
                            0 => 'bool',
                        ],
                ],
        ];

        $expectedDoc = '/**
 * some desc
 *
 * @param int $bla
 *
 * @return bool
 */
';

        $this->assertSame($expectedDoc, DocComment::render($docComment, ''));
    }

    public function testNewLineIsNotAddedInDocBlockBeforeReturnIfDisabled(): void
    {
        DocComment::addNewLineBeforeReturn(false);

        $docComment = [
            'description' => 'some desc',
            'specials' =>
                [
                    'param' =>
                        [
                            3 => 'int $bla',
                        ],
                    'return' =>
                        [
                            0 => 'bool',
                        ],
                ],
        ];

        $expectedDoc = '/**
 * some desc
 *
 * @param int $bla
 * @return bool
 */
';

        $this->assertSame($expectedDoc, DocComment::render($docComment, ''));
    }

    public function testNewLineIsAddedInDocBlockBeforeReturnIfEnabled(): void
    {
        DocComment::addNewLineBeforeReturn(true);

        $docComment = [
            'description' => 'some desc',
            'specials' =>
                [
                    'param' =>
                        [
                            3 => 'int $bla',
                        ],
                    'return' =>
                        [
                            0 => 'bool',
                        ],
                ],
        ];

        $expectedDoc = '/**
 * some desc
 *
 * @param int $bla
 *
 * @return bool
 */
';

        $this->assertSame($expectedDoc, DocComment::render($docComment, ''));
    }
}
