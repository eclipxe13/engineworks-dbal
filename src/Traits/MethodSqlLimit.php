<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;

/** @var DBAL $this */
trait MethodSqlLimit
{
    public function sqlLimit(string $query, int $requestedPage, int $recordsPerPage = 20): string
    {
        $requestedPage = max(1, $requestedPage) - 1; // zero indexed
        $recordsPerPage = max(1, $recordsPerPage);
        $query = rtrim($query, "; \t\n\r\0\x0B")
            . ' LIMIT ' . $this->sqlQuote($recordsPerPage, CommonTypes::TINT)
            . ' OFFSET ' . $this->sqlQuote($recordsPerPage * $requestedPage, CommonTypes::TINT)
            . ';';
        return $query;
    }
}
