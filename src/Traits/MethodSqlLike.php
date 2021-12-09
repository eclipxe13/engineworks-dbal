<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Traits;

use EngineWorks\DBAL\DBAL;

/** @var DBAL $this */
trait MethodSqlLike
{
    public function sqlLike(
        string $fieldName,
        string $searchString,
        bool $wildcardBegin = true,
        bool $wildcardEnd = true
    ): string {
        return $fieldName
            . " LIKE '"
            . (($wildcardBegin) ? '%' : '')
            . $this->sqlString($searchString)
            . (($wildcardEnd) ? '%' : '')
            . "'";
    }
}
