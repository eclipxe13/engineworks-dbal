<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\DBAL;

/** @var DBAL $this */
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
