<?php
namespace Psalm\Test\Config\Plugin\Hook;

use PhpMyAdmin\SqlParser\Parser;
use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Plugin\EventHandler\StringInterpreterInterface;
use Psalm\Test\Config\Plugin\Hook\StringProvider\TSqlSelectString;
use Psalm\Type\Atomic\TLiteralString;
use Throwable;

use function stripos;

class SqlStringProvider implements StringInterpreterInterface
{
    public static function getTypeFromValue(StringInterpreterEvent $event): ?TLiteralString
    {
        $value = $event->getValue();
        if (stripos($value, 'select ') !== false) {
            try {
                $parser = new Parser($value);

                if (!$parser->errors) {
                    return new TSqlSelectString($value);
                }
            } catch (Throwable $e) {
                // fall through
            }
        }

        return null;
    }
}
