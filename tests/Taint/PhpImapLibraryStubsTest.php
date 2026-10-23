<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for webklex/php-imap, which Psalm loads because the code below declares classes
 * of the package, as its sources do
 */
final class PhpImapLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Webklex\PHPIMAP {
                class Message {}
                class Attribute {}
                class Attachment {}
            }';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'email' => [
                '<?php
                    use Webklex\PHPIMAP\Attachment;
                    use Webklex\PHPIMAP\Attribute;
                    use Webklex\PHPIMAP\Message;

                    function show(Message $message, Attribute $subject, Attachment $attachment): void {
                        echo $message->getTextBody();
                        echo $subject->toString();
                        echo $attachment->getName();
                    }',
                [
                    'TaintedHtml on line 7',
                    'TaintedHtml on line 8',
                    'TaintedHtml on line 9',
                    'TaintedTextWithQuotes on line 7',
                    'TaintedTextWithQuotes on line 8',
                    'TaintedTextWithQuotes on line 9',
                ],
            ],
        ];
    }
}
