<?php
namespace EngineWorks\DBAL\Traits;

trait MethodSqlConcatenate
{
    public function sqlConcatenate(...$strings)
    {
        if (! count($strings)) {
            return $this->sqlQuote('');
        }
        return 'CONCAT(' . implode(', ', $strings) . ')';
    }
}
