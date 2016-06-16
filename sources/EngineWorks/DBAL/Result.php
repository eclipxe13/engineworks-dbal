<?php namespace EngineWorks\DBAL;

/**
 * Interface Result, this interface must be implemented by the Drivers
 *
 * @package EngineWorks\DBAL
 */
interface Result
{
    /**
     * Get one row from the resource, the result is an associative array
     * @return array|false Return FALSE on error
     */
    public function fetchRow();

    /**
     * Return an array with the fields information
     * Each row must contain the keys: [name, commontype, table]
     * @return array|false
     */
    public function getFields();

    /**
     * Get an array with the names of the ids elements
     * @return array|false array of primary keys, false if not found
     */
    public function getIdFields();

    /**
     * Retrieve the count of the resource
     * @return int
     */
    public function resultCount();

    /**
     * Move to the first row in the result (if any result)
     * @return bool
     */
    public function moveFirst();

    /**
     * Try to move to a specified row in the resource, the first row is always zero
     * @param int $offset
     * @return bool
     */
    public function moveTo($offset);
}
