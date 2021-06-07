<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Plugin\EventHandler\StringInterpreterInterface;
use Psalm\Type\Atomic\TLiteralString;

use function stripos;

class SqlStringProvider implements StringInterpreterInterface
{
    public static function getTypeFromValue(StringInterpreterEvent $event) : ?TLiteralString
    {
        $value = $event->getValue();
        if (stripos($value, 'select ') !== false) {
            try {
                $parser = new \PhpMyAdmin\SqlParser\Parser($value);

                if (!$parser->errors) {
                    return new StringProvider\TSqlSelectString($value);
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return null;
    }
}
