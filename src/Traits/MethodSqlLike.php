<?php
namespace EngineWorks\DBAL\Traits;

trait MethodSqlLike
{
    public function sqlLike($fieldName, $searchString, $wildcardBegin = true, $wildcardEnd = true)
    {
        return $fieldName
            . " LIKE '"
            . (($wildcardBegin) ? '%' : '')
            . $this->sqlString($searchString)
            . (($wildcardEnd) ? '%' : '')
            . "'"
            ;
    }
}
