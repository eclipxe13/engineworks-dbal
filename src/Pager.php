<?php
namespace EngineWorks\DBAL;

use EngineWorks\DBAL\Exceptions\QueryException;

/**
 * Pagination
 * @package EngineWorks\DBAL
 */
class Pager
{
    /**
     * This count method is used when another query to retrieve the total records is provided
     */
    const COUNT_METHOD_QUERY = 0;

    /**
     * This count method is used to create a select count(*) with the data query as subquery
     */
    const COUNT_METHOD_SELECT = 1;

    /**
     * This count method is used to retrieve the total records by
     */
    const COUNT_METHOD_RECORDCOUNT = 2;

    /** @var DBAL */
    private $dbal;

    /** @var Recordset|null */
    private $recordset;

    /** @var string SQL to query the data */
    private $queryData;

    /** @var string SQL to query the count */
    private $queryCount;

    /** @var int */
    private $pageSize;

    /**
     * One of COUNT_METHOD_QUERY, COUNT_METHOD_SELECT, COUNT_METHOD_RECORDCOUNT
     * This is set when the object is created,
     * Using its setter for COUNT_METHOD_SELECT, COUNT_METHOD_RECORDCOUNT
     * Using setQueryCount for COUNT_METHOD_QUERY
     *
     * @var int
     */
    private $countMethod;

    /** @var int number of the current page */
    private $page;

    /**
     * If NULL then the value needs to be read from database
     *
     * @var int|null
     */
    private $totalRecords = null;

    /**
     * Instantiate a pager object
     * If the queryCount is not set then it will set the method COUNT_METHOD_SELECT
     * that will query a count(*) using $queryData as a subquery
     *
     * @param DBAL $dbal
     * @param string $queryData The sql sentence to retrieve the data, do not use any LIMIT here
     * @param string $queryCount The sql sentence to retrieve the count of the data
     * @param int $pageSize The page size
     */
    public function __construct(DBAL $dbal, string $queryData, string $queryCount = '', int $pageSize = 20)
    {
        $this->dbal = $dbal;
        $this->queryData = $queryData;
        if ('' !== $queryCount) {
            // this method also calls $this->setCountMethod
            $this->setQueryCount($queryCount);
        } else {
            // set a non-null value, otherwise setCountMethod will fail
            $this->countMethod = -1;
            $this->setCountMethod(self::COUNT_METHOD_SELECT);
        }
        $this->setPageSize($pageSize);
    }

    /**
     * perform the query to get a limited result
     * @param int $requestedPage
     * @return bool
     */
    public function queryPage(int $requestedPage): bool
    {
        // clear
        $this->page = 0;
        $this->totalRecords = null;
        $this->recordset = null;
        // request
        $page = min($this->getTotalPages(), max(1, $requestedPage));
        $query = $this->dbal->sqlLimit($this->getQueryData(), $page, $this->getPageSize());
        $recordset = $this->dbal->queryRecordset($query);
        if (false === $recordset) {
            return false;
        }
        $this->page = $page;
        $this->recordset = $recordset;
        return true;
    }

    /**
     * perform the query to get all the records (without paging)
     * @return bool
     */
    public function queryAll(): bool
    {
        $this->setPageSize($this->getTotalCount());
        return $this->queryPage(1);
    }

    /**
     * The current page number
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * The current recordset object
     * @return Recordset
     */
    public function getRecordset(): Recordset
    {
        if (! $this->recordset instanceof Recordset) {
            throw new \RuntimeException('The pager does not have a current page');
        }
        return $this->recordset;
    }

    /**
     * The SQL to query the data
     * @return string
     */
    public function getQueryData(): string
    {
        return $this->queryData;
    }

    /**
     * The SQL to query the count of records
     * @return string
     */
    public function getQueryCount(): string
    {
        return $this->queryCount;
    }

    /**
     * Set the SQL to query the count of records
     * This set the countMethod to COUNT_METHOD_QUERY
     * @param string $query
     * @return void
     */
    protected function setQueryCount(string $query)
    {
        if ('' === $query) {
            throw new \InvalidArgumentException('setQueryCount require a valid string argument');
        }
        $this->queryCount = $query;
        $this->countMethod = self::COUNT_METHOD_QUERY;
    }

    /**
     * Get the page size
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Get the total count based on the count method
     * @return int
     */
    public function getTotalCount(): int
    {
        if (null === $this->totalRecords) {
            if ($this->getCountMethod() === self::COUNT_METHOD_QUERY) {
                $this->totalRecords = $this->getTotalRecordsByQueryCount();
            } elseif ($this->getCountMethod() === self::COUNT_METHOD_SELECT) {
                $this->totalRecords = $this->getTotalRecordsBySelectCount();
            } elseif ($this->getCountMethod() === self::COUNT_METHOD_RECORDCOUNT) {
                $this->totalRecords = $this->getTotalRecordsByRecordCount();
            } else {
                throw new \LogicException('Cannot get a method to obtain the total count');
            }
        }
        return $this->totalRecords;
    }

    /**
     * The count method, ne of COUNT_METHOD_QUERY, COUNT_METHOD_SELECT, COUNT_METHOD_RECORDCOUNT
     * @return int
     */
    public function getCountMethod(): int
    {
        return $this->countMethod;
    }

    /**
     * Change the count method, the only possible values are
     * COUNT_METHOD_SELECT and COUNT_METHOD_RECORDCOUNT
     * Return the previous count method set
     * @param int $method
     * @return int
     */
    public function setCountMethod(int $method): int
    {
        if (! in_array($method, [self::COUNT_METHOD_SELECT, self::COUNT_METHOD_RECORDCOUNT])) {
            throw new \InvalidArgumentException('Invalid count method');
        }
        $previous = $this->countMethod;
        $this->countMethod = $method;
        return $previous;
    }

    protected function getTotalRecordsByRecordCount(): int
    {
        $query = $this->getQueryData();
        $result = $this->dbal->queryResult($query);
        if (false === $result) {
            throw new \RuntimeException("Unable to query the record count by getting all the results: $query");
        }
        return $result->resultCount();
    }

    protected function getTotalRecordsBySelectCount(): int
    {
        $query = 'SELECT COUNT(*)'
            . ' FROM (' . rtrim($this->queryData, "; \t\n\r\0\x0B") . ')'
            . ' AS ' . $this->dbal->sqlTable('subquerycount')
            . ';';
        $value = (int) $this->dbal->queryOne($query, -1);
        if (-1 === $value) {
            throw new QueryException($query, 'Unable to query the record count using a subquery');
        }
        return $value;
    }

    protected function getTotalRecordsByQueryCount(): int
    {
        $query = $this->getQueryCount();
        $value = (int) $this->dbal->queryOne($query, -1);
        if (-1 === $value) {
            throw new QueryException($query, 'Unable to query the record count using a query');
        }
        return $value;
    }

    /**
     * Number of total pages (min: 1, max: total count / page size)
     * @return int
     */
    public function getTotalPages(): int
    {
        return max(1, ceil($this->getTotalCount() / $this->getPageSize()));
    }

    /**
     * Set the page size, this is fixes to a minimum value of 1
     * @param int $pageSize
     * @return void
     */
    public function setPageSize(int $pageSize)
    {
        $this->pageSize = max(1, $pageSize);
    }
}
