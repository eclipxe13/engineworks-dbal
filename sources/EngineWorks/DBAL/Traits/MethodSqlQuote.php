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
        if ($includeNull && is_null($variable)) {
            return 'NULL';
        }
        switch (strtoupper($commonType)) {
            case CommonTypes::TINT:
                return $this->sqlQuoteParseNumber($variable, true);
            case CommonTypes::TNUMBER:
                return $this->sqlQuoteParseNumber($variable, false);
            case CommonTypes::TBOOL:
                return ($variable) ? '1' : '0';
            case CommonTypes::TDATE:
                return "'" . date('Y-m-d', intval($variable, 10)) . "'";
            case CommonTypes::TTIME:
                return "'" . date('H:i:s', intval($variable, 10)) . "'";
            case CommonTypes::TDATETIME:
                return "'" . date('Y-m-d H:i:s', intval($variable, 10)) . "'";
            default:
                return "'" . $this->sqlString($variable) . "'";
        }
    }
}
