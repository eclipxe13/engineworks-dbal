<?php
namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;

trait MethodSqlQuote
{
    private function sqlQuoteParseNumber($value, $asInteger = true)
    {
        $isIntOrFloat = is_int($value) || is_float($value);
        if ($asInteger && $isIntOrFloat) {
            return intval($value);
        }
        if (! $asInteger && $isIntOrFloat) {
            return floatval($value);
        }
        if (is_object($value)) {
            $value = strval($value);
        }
        if (! is_string($value)) {
            return 0;
        }
        $value = trim($value);
        if ('' === $value) {
            return 0;
        }
        if ('C' === setlocale(LC_NUMERIC, 0)) {
            $localeConv = ['thousands_sep' => ',', 'currency_symbol' => '$', 'int_curr_symbol' => '$'];
        } else {
            $localeConv = localeconv();
        }
        $replacements = [
            $localeConv['thousands_sep'],
            $localeConv['currency_symbol'],
            $localeConv['int_curr_symbol'],
            ' ',
        ];
        $value = str_replace($replacements, '', $value);
        return (is_numeric($value)) ? (($asInteger) ? intval($value, 10) : floatval($value)) : 0;
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
