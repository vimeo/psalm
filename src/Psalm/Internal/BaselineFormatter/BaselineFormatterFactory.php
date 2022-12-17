<?php

declare(strict_types=1);

namespace Psalm\Internal\BaselineFormatter;

use Exception;
use Psalm\Config;

use function is_string;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * @internal
 */
final class BaselineFormatterFactory
{
    public function fromKey(string $key): BaselineFormatterInterface
    {
        if ($key === XmlBaselineFormatter::getKey()) {
            return new XmlBaselineFormatter();
        } elseif ($key === JsonBaselineFormatter::getKey()) {
            return new JsonBaselineFormatter();
        } else {
            throw new Exception('Unknown baseline formatter key: ' . $key);
        }
    }

    public function fromOptionsAndConfig(array $options, Config $config): BaselineFormatterInterface
    {
        if (isset($options['baseline-formatter'])) {
            if (!is_string($options['baseline-formatter'])) {
                throw new Exception('baseline-formatter option is not a string.');
            }
            $key = $options['baseline-formatter'];
        } elseif (isset($options['set-baseline'])) {
            $extension = pathinfo($options['set-baseline'], PATHINFO_EXTENSION);
            $key = $extension !== '' ? $extension : XmlBaselineFormatter::getKey();
        } elseif ($config->error_baseline) {
            $extension = pathinfo($config->error_baseline, PATHINFO_EXTENSION);
            $key = $extension !== '' ? $extension : XmlBaselineFormatter::getKey();
        } else {
            $key = XmlBaselineFormatter::getKey();
        }
        return $this->fromKey($key);
    }
}
