<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Internal\NumericParser;

/** @var DBAL $this */
trait MethodSqlQuote
{
    public function sqlQuote($variable, string $commonType = CommonTypes::TTEXT, bool $includeNull = false): string
    {
        if ($includeNull && null === $variable) {
            return 'NULL';
        }
        // CommonTypes::TTEXT is here because is the most common used type
        if (CommonTypes::TTEXT === $commonType) {
            return "'" . $this->sqlString($variable) . "'";
        }
        if (CommonTypes::TINT === $commonType) {
            return $this->sqlQuoteParseNumber($variable, true);
        }
        if (CommonTypes::TNUMBER === $commonType) {
            return $this->sqlQuoteParseNumber($variable, false);
        }
        if (CommonTypes::TBOOL === $commonType) {
            return ($variable) ? '1' : '0';
        }
        if (CommonTypes::TDATE === $commonType) {
            return "'" . date('Y-m-d', (int) $variable) . "'";
        }
        if (CommonTypes::TTIME === $commonType) {
            return "'" . date('H:i:s', intval($variable, 10)) . "'";
        }
        if (CommonTypes::TDATETIME === $commonType) {
            return "'" . date('Y-m-d H:i:s', intval($variable, 10)) . "'";
        }
        return "'" . $this->sqlString($variable) . "'";
    }

    /**
     * @param mixed $value
     * @param bool $asInteger
     * @return string
     */
    private function sqlQuoteParseNumber($value, bool $asInteger = true): string
    {
        return (new NumericParser())->parseAsEnglish($value, $asInteger);
    }
}
