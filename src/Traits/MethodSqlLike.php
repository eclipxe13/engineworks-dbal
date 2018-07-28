<?php
namespace EngineWorks\DBAL\Traits;

/** @var \EngineWorks\DBAL\DBAL $this */
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
