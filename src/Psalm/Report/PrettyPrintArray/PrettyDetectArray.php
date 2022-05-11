<?php

namespace Psalm\Report\PrettyPrintArray;

use Generator;

use function str_split;
use function strpos;
use function substr;

final class PrettyDetectArray
{
    public function detect(string $workingPayload): ?Generator
    {
        $arrayPayload = $this->getFirstArrayPayload($workingPayload);
        yield $arrayPayload['payload'];

        $arrayPayload = $this->getFirstArrayPayload($arrayPayload['nextPayload']);
        yield $arrayPayload['payload'];
    }

    private function getPositionOfEndArray(string $payload): ?int
    {
        $countChar = 0;
        $prettyCursorBracket = new PrettyCursorBracket();

        $positionArrayWord = strpos($payload, 'array');
        if ($positionArrayWord === false) {
            return null;
        }

        $arrayPayload = substr($payload, $positionArrayWord);

        foreach (str_split($arrayPayload) as $char) {
            $countChar++;

            $prettyCursorBracket->accept($char);
            if ($prettyCursorBracket->closed()) {
                break;
            }
        }

        return $countChar;
    }

    /**
     * @return array {
     *  payload: string,
     *  start: int
     *  end: int,
     *  nextPayload: string
     * }
     */
    private function getFirstArrayPayload(string $payload): ?array
    {
        $posStart = strpos($payload, 'array');
        if ($posStart === false) {
            return null;
        }
        $posEnd = $this->getPositionOfEndArray($payload);

        $payloadArray = substr($payload, $posStart,$posEnd);
        $nextPayload = substr($payload, $posStart+$posEnd);

        return [
            'payload' => $payloadArray,
            'start' => $posStart,
            'end' => $posEnd,
            'nextPayload' => $nextPayload
        ];
    }
}
