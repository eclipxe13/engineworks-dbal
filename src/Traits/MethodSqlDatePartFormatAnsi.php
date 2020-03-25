<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use InvalidArgumentException;

trait MethodSqlDatePartFormatAnsi
{
    private function sqlDatePartFormatAnsi(string $part): string
    {
        switch (strtoupper($part)) {
            case 'YEAR':
                $format = '%Y';
                break;
            case 'MONTH':
                $format = '%m';
                break;
            case 'FDOM':
                $format = '%Y-%m-01';
                break;
            case 'FYM':
                $format = '%Y-%m';
                break;
            case 'FYMD':
                $format = '%Y-%m-%d';
                break;
            case 'DAY':
                $format = '%d';
                break;
            case 'HOUR':
                $format = '%H';
                break;
            case 'MINUTE':
                $format = '%i';
                break;
            case 'SECOND':
                $format = '%s';
                break;
            default:
                throw new InvalidArgumentException("Date part $part is not valid");
        }
        return $format;
    }
}
