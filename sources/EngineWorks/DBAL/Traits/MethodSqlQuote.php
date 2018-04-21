<?php
namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Internal\NumericParser;

trait MethodSqlQuote
{
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
                return strval($this->sqlQuoteParseNumber($variable, true));
            case CommonTypes::TNUMBER:
                return strval($this->sqlQuoteParseNumber($variable, false));
            case CommonTypes::TBOOL:
                return ($variable) ? '1' : '0';
            case CommonTypes::TDATE:
                return "'" . date('Y-m-d', $this->sqlQuoteParseNumber($variable, true)) . "'";
            case CommonTypes::TTIME:
                return "'" . date('H:i:s', $this->sqlQuoteParseNumber($variable, true)) . "'";
            case CommonTypes::TDATETIME:
                return "'" . date('Y-m-d H:i:s', $this->sqlQuoteParseNumber($variable, true)) . "'";
            default:
                return "'" . $this->sqlString($variable) . "'";
        }
    }
}
