<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\DocComment;

class DocCommentTest extends BaseTestCase
{
    public function testNewLineIsAddedBetweenAnnotationsByDefault(): void
    {
        $docComment = [
            'description' => 'some desc',
            'specials' =>
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
                ],
        ];

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

        $this->assertSame($expectedDoc, DocComment::render($docComment, ''));
    }

    public function testNewLineIsNotAddedBetweenAnnotationsIfDisabled(): void
    {
        DocComment::addNewLineBetweenAnnotations(false);

        $docComment = [
            'description' => 'some desc',
            'specials' =>
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
                ],
        ];

        $expectedDoc = '/**
 * some desc
 *
 * @param string $bli
 * @param int $bla
 * @throws \Exception
 * @return bool
 */
';

        $this->assertSame($expectedDoc, DocComment::render($docComment, ''));
    }

    public function testNewLineIsAddedBetweenAnnotationsIfEnabled(): void
    {
        DocComment::addNewLineBetweenAnnotations(true);

        $docComment = [
            'description' => 'some desc',
            'specials' =>
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
                ],
        ];

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

        $this->assertSame($expectedDoc, DocComment::render($docComment, ''));
    }
}
