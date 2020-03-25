<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Internal;

/**
 * This class parses a string expression as a number or as in plain english (as need to put inside a sql statement)
 *
 * @internal Is not intented to be used outside the application
 */
class NumericParser
{
    /** @var array|null Contains the running locale information */
    private $localeConv = null;

    /**
     * @param mixed $value
     * @param bool $asInteger
     * @return int|float
     */
    public function parse($value, bool $asInteger)
    {
        // return simple numeric data
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return ($asInteger) ? intval($value) : floatval($value);
        }

        // is object, convert to string
        if (is_object($value)) {
            $value = strval($value);
        }
        // is not string, early exit with 0
        if (! is_string($value)) {
            return 0;
        }
        $value = $this->parseToEnglish(trim($value));
        return ($asInteger) ? intval($value) : floatval($value);
    }

    public function parseAsEnglish($value, bool $asInteger): string
    {
        return $this->numberToEnglish((string) $this->parse($value, $asInteger));
    }

    protected function getLocaleInfo(): array
    {
        if (! is_array($this->localeConv)) {
            $this->localeConv = $this->obtainLocaleInfo();
        }
        return $this->localeConv;
    }

    protected function obtainLocaleInfo(): array
    {
        if ('C' === setlocale(LC_NUMERIC, '0')) {
            // override to us_EN
            $localeConv = ['decimal_point' => '.', 'thousands_sep' => ',', 'currency_symbol' => '$'];
        } else {
            $localeConv = localeconv();
        }
        return $localeConv;
    }

    /**
     * Remove the thousands separator, currency symbol and white spaces.
     * Returns the resulting string if is numeric,  otherwise returns '0'
     *
     * @param string $value
     * @return string
     */
    protected function parseToEnglish(string $value): string
    {
        if (ctype_digit($value)) {
            return $value;
        }
        $localeConv = $this->getLocaleInfo();
        $replacements = [$localeConv['thousands_sep'], $localeConv['currency_symbol'], ' ', "\t"];
        $value = $this->numberToEnglish(str_replace($replacements, '', $value));
        return (! is_numeric($value)) ? '0' : $value;
    }

    /**
     * Change decimal point to a real point
     * @param string $value
     * @return string
     */
    protected function numberToEnglish(string $value): string
    {
        if ('.' !== $this->getDecimalPoint()) {
            return str_replace($this->getDecimalPoint(), '.', $value);
        }
        return $value;
    }

    protected function getDecimalPoint(): string
    {
        return $this->getLocaleInfo()['decimal_point'] ?? '';
    }
}
