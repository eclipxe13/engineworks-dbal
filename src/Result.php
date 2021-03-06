<?php
namespace EngineWorks\DBAL;

/**
 * Interface Result, this interface must be implemented by the Drivers
 *
 * @package EngineWorks\DBAL
 */
interface Result extends \IteratorAggregate, \Countable
{
    /**
     * Get one row from the resource, the result is an associative array
     * @return array|false Return FALSE on error
     */
    public function fetchRow();

    /**
     * Return an array with the fields information
     * Each row must contain the keys: [name, commontype, table]
     * @return array
     */
    public function getFields(): array;

    /**
     * Get an array with the names of the ids elements
     * @todo do not return false, return an empty array
     * @return array|false array of primary keys, false if not found
     */
    public function getIdFields();

    /**
     * Retrieve the count of the resource
     * @return int
     */
    public function resultCount(): int;

    /**
     * Move to the first row in the result (if any result)
     * @return bool
     */
    public function moveFirst(): bool;

    /**
     * Try to move to a specified row in the resource, the first row is always zero
     * @param int $offset
     * @return bool
     */
    public function moveTo(int $offset): bool;

    /**
     * Retrieve an iterator
     * @return \Iterator
     */
    public function getIterator();

    /**
     * Return the total recprds of the resultset
     * @return int
     */
    public function count(): int;
}
