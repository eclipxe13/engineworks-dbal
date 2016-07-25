<?php namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\CommonTypes;

trait MethodSqlLimit
{
    public function sqlLimit($query, $requestedPage, $recordsPerPage = 20)
    {
        $requestedPage = max(1, (int) $requestedPage) - 1; // zero indexed
        $recordsPerPage = max(1, (int) $recordsPerPage);
        $query = rtrim($query, "; \t\n\r\0\x0B")
            . ' LIMIT ' . $this->sqlQuote($recordsPerPage, CommonTypes::TINT)
            . ' OFFSET ' . $this->sqlQuote($recordsPerPage * $requestedPage, CommonTypes::TINT) ;
        return $query;
    }
}
