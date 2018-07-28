<?php
namespace EngineWorks\DBAL\Traits;

/** @var \EngineWorks\DBAL\DBAL $this */
trait MethodSqlConcatenate
{
    public function sqlConcatenate(...$strings): string
    {
        if (! count($strings)) {
            return $this->sqlQuote('');
        }
        return 'CONCAT(' . implode(', ', $strings) . ')';
    }
}
