<?php
namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Internal\NumericParser;

trait MethodSqlQuote
{
    /**
     * @param mixed $value
     * @param bool $asInteger
     * @return string
     */
    private function sqlQuoteParseNumber($value, $asInteger = true)
    {
        return (new NumericParser())->parseAsEnglish($value, $asInteger);
    }

    public function sqlQuote($variable, $commonType = CommonTypes::TTEXT, $includeNull = false)
    {
        if ($includeNull && null === $variable) {
            return 'NULL';
        }
        // CommonTypes::TTEXT is here because is the most common used type
        if ($commonType === CommonTypes::TTEXT) {
            return "'" . $this->sqlString($variable) . "'";
        }
        if ($commonType === CommonTypes::TINT) {
            return $this->sqlQuoteParseNumber($variable, true);
        }
        if ($commonType === CommonTypes::TNUMBER) {
            return $this->sqlQuoteParseNumber($variable, false);
        }
        if ($commonType === CommonTypes::TBOOL) {
            return ($variable) ? '1' : '0';
        }
        if ($commonType === CommonTypes::TDATE) {
            return "'" . date('Y-m-d', (int) $variable) . "'";
        }
        if ($commonType === CommonTypes::TTIME) {
            return "'" . date('H:i:s', intval($variable, 10)) . "'";
        }
        if ($commonType === CommonTypes::TDATETIME) {
            return "'" . date('Y-m-d H:i:s', intval($variable, 10)) . "'";
        }
        return "'" . $this->sqlString($variable) . "'";
    }
    }
}
