<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpParser\Comment\Doc;
use Psalm\DocComment;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\ParsedDocblock;

class DocCommentTest extends BaseTestCase
{
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
    }

    public function testNewLineIsAddedBetweenAnnotationsByDefault(): void
    {
        $docComment = new ParsedDocblock(
            'some desc',
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
        );

        $expectedDoc = <<<'PHP'
            /**
             * some desc
             *
             * @param string $bli
             * @param int $bla
             *
             * @throws \Exception
             *
             * @return bool
             */

            PHP;

        $this->assertSame($expectedDoc, $docComment->render(''));
    }

    public function testNewLineIsNotAddedBetweenAnnotationsIfDisabled(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(false);

        $docComment = new ParsedDocblock(
            'some desc',
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
        );

        $expectedDoc = <<<'PHP'
            /**
             * some desc
             *
             * @param string $bli
             * @param int $bla
             * @throws \Exception
             * @return bool
             */

            PHP;

        $this->assertSame($expectedDoc, $docComment->render(''));
    }

    public function testNewLineIsAddedBetweenAnnotationsIfEnabled(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(true);

        $docComment = new ParsedDocblock(
            'some desc',
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
        );

        $expectedDoc = <<<'PHP'
            /**
             * some desc
             *
             * @param string $bli
             * @param int $bla
             *
             * @throws \Exception
             *
             * @return bool
             */

            PHP;

        $this->assertSame($expectedDoc, $docComment->render(''));
    }

    public function testParsingRoundtrip(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(true);

        $expectedDoc = <<<'PHP'
            /**
             * some desc
             *
             * @param string $bli
             * @param int $bla
             *
             * @throws \Exception
             *
             * @return bool
             */

            PHP;
        $docComment = DocComment::parsePreservingLength(
            new Doc($expectedDoc),
        );

        $this->assertSame($expectedDoc, $docComment->render(''));
    }

    public function testParsingWithIndentation(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(true);

        $expectedDoc = <<<'PHP'
            /**
                 * some desc
                 *
                 * @param string $bli
                 * @param int $bla
                 *
                 * @throws \Exception
                 *
                 * @return bool
                 */

            PHP
            . "    ";

        $docComment = DocComment::parsePreservingLength(
            new Doc($expectedDoc),
        );

        $this->assertSame($expectedDoc, $docComment->render('    '));
    }

    public function testParsingWithCommonPrefixes(): void
    {
        ParsedDocblock::addNewLineBetweenAnnotations(true);

        $expectedDoc = <<<'PHP'
            /**
             * some self-referential desc with " * @return bool
             * " as part of it.
             *
             * @param string $bli
             * @param string $bli_this_suffix_is_kept
             * @param int $bla
             *
             * @throws \Exception
             *
             * @return bool
             */

            PHP;
        $docComment = DocComment::parsePreservingLength(
            new Doc($expectedDoc),
        );

        $this->assertSame($expectedDoc, $docComment->render(''));
    }
}
