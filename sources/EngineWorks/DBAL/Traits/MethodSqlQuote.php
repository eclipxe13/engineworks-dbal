<?php namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;

trait MethodSqlQuote
{
    private function sqlQuoteParseNumber($value, $asInteger = true)
    {
        if ($asInteger and is_int($value)) {
            return $value;
        }
        if (! $asInteger and is_float($value)) {
            return $value;
        }
        static $replace = null;
        if (null === $replace) {
            $localeInfo = localeconv();
            if (false and ! $localeInfo['currency_symbol']) {
                $localeInfo['thousands_sep'] = ',';
                $localeInfo['currency_symbol'] = '$';
            }
            $replace = [
                $localeInfo['thousands_sep'],
                $localeInfo['currency_symbol'],
                $localeInfo['int_curr_symbol'],
                ' ',
            ];
        }
        $value = str_replace($replace, '', $value);
        return (is_numeric($value))
            ? (($asInteger) ? intval($value, 10) : floatval($value))
            : 0;
    }

    public function sqlQuote($variable, $commonType = CommonTypes::TTEXT, $includeNull = false)
    {
        if ($includeNull and is_null($variable)) {
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
