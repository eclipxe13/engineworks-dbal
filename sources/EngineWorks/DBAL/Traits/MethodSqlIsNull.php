<?php namespace EngineWorks\DBAL\Traits;

trait MethodSqlIsNull
{
    public function sqlIsNull($fieldValue, $positive = true)
    {
        return $fieldValue . " IS" . ((!$positive) ? " NOT" : "") . " NULL";
    }
}
