<?php
namespace EngineWorks\DBAL;

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
    public function __construct(DBAL $dbal, $queryData, $queryCount = '', $pageSize = 20)
    {
        $this->dbal = $dbal;
        $this->queryData = $queryData;
        if ('' !== $queryCount) {
            // this method also calls $this->setCountMethod
            $this->setQueryCount($queryCount);
        } else {
            $this->setCountMethod(self::COUNT_METHOD_SELECT);
        }
        $this->setPageSize($pageSize);
    }

    /**
     * perform the query to get a limited result
     * @param int $requestedPage
     * @return bool
     */
    public function queryPage($requestedPage)
    {
        // clear
        $this->page = 0;
        $this->totalRecords = null;
        $this->recordset = null;
        // request
        $page = (int) min($this->getTotalPages(), max(1, intval($requestedPage)));
        $query = $this->dbal->sqlLimit($this->getQueryData(), $page, $this->getPageSize());
        $recordset = $this->dbal->queryRecordset($query);
        if (! $recordset instanceof Recordset) {
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
    public function queryAll()
    {
        $this->setPageSize($this->getTotalCount());
        return $this->queryPage(1);
    }

    /**
     * The current page number
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * The current recordset object
     * @return Recordset|null
     */
    public function getRecordset()
    {
        return $this->recordset;
    }

    /**
     * The SQL to query the data
     * @return string
     */
    public function getQueryData()
    {
        return $this->queryData;
    }

    /**
     * The SQL to query the count of records
     * @return string
     */
    public function getQueryCount()
    {
        return $this->queryCount;
    }

    /**
     * Set the SQL to query the count of records
     * This set the countMethod to COUNT_METHOD_QUERY
     * @param string $query
     */
    protected function setQueryCount($query)
    {
        if (! is_string($query) || empty($query)) {
            throw new \InvalidArgumentException('setQueryCount require a valid string argument');
        }
        $this->queryCount = $query;
        $this->countMethod = self::COUNT_METHOD_QUERY;
    }

    /**
     * Get the page size
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Get the total count based on the count method
     * @return int
     */
    public function getTotalCount()
    {
        if (null === $this->totalRecords) {
            if ($this->getCountMethod() === self::COUNT_METHOD_QUERY) {
                $this->totalRecords = $this->getTotalRecordsByQueryCount();
            } elseif ($this->getCountMethod() === self::COUNT_METHOD_SELECT) {
                $this->totalRecords = $this->getTotalRecordsBySelectCount();
            } elseif ($this->getCountMethod() === self::COUNT_METHOD_RECORDCOUNT) {
                $this->totalRecords = $this->getTotalRecordsByRecordCount();
            }
        }
        return $this->totalRecords;
    }

    /**
     * The count method, ne of COUNT_METHOD_QUERY, COUNT_METHOD_SELECT, COUNT_METHOD_RECORDCOUNT
     * @return int
     */
    public function getCountMethod()
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
    public function setCountMethod($method)
    {
        if (! in_array($method, [self::COUNT_METHOD_SELECT, self::COUNT_METHOD_RECORDCOUNT])) {
            throw new \InvalidArgumentException('Invalid count method');
        }
        $previous = $this->countMethod;
        $this->countMethod = $method;
        return $previous;
    }

    /**
     * @return int
     */
    protected function getTotalRecordsByRecordCount()
    {
        $query = $this->getQueryData();
        $result = $this->dbal->queryResult($query);
        if (false === $result) {
            throw new \RuntimeException("Unable to query the record count by getting all the results: $query");
        }
        return $result->resultCount();
    }

    /**
     * @return int
     */
    protected function getTotalRecordsBySelectCount()
    {
        $query = 'SELECT COUNT(*)'
            . ' FROM (' . rtrim($this->queryData, "; \t\n\r\0\x0B") . ')'
            . ' AS ' . $this->dbal->sqlTable('subquerycount')
            . ';';
        $value = $this->dbal->queryOne($query, false);
        if (false === $value) {
            throw new \RuntimeException("Unable to query the record count using a subquery: $query");
        }
        return (int) $value;
    }

    /**
     * @return int
     */
    protected function getTotalRecordsByQueryCount()
    {
        $query = $this->getQueryCount();
        $value = $this->dbal->queryOne($query, false);
        if (false === $value) {
            throw new \RuntimeException("Unable to query the record count using a query: $query");
        }
        return (int) $value;
    }

    /**
     * Number of total pages (min: 1, max: total count / page size)
     * @return int
     */
    public function getTotalPages()
    {
        return max(1, ceil($this->getTotalCount() / $this->getPageSize()));
    }

    /**
     * Set the page size, this is fixes to a minimum value of 1
     * @param int $pageSize
     */
    public function setPageSize($pageSize)
    {
        if (! is_numeric($pageSize)) {
            throw new \InvalidArgumentException('The page size property is not a number');
        }
        $this->pageSize = max(1, intval($pageSize));
    }
}
