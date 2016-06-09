<?php namespace EngineWorks\DBAL;

use \Psr\Log\LoggerInterface;
use \Psr\Log\LoggerAwareTrait;
use \Psr\Log\NullLogger;

/**
 * Database Abstraction Layer Abstract Class
 */
abstract class DBAL
{

    /* -----
     * Public constants about common types
     */

    const TDATE = "DATE";
    const TTIME = "TIME";
    const TDATETIME = "DATETIME";
    const TTEXT = "TEXT";
    const TNUMBER = "NUMBER";
    const TINT = "INT";
    const TBOOL = "BOOL";

    /* -----
     * Logger
     */
    use LoggerAwareTrait;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }


    /* -----
     * protected variables about the object
     */

    /**
     * Settings object
     * @var Settings
     */
    protected $settings;

    /* -----
     * magic methods
     */

    /**
     * @param Settings $settings
     * @param LoggerInterface|null $logger If null then a NullLogger will be used
     */
    public function __construct(Settings $settings, LoggerInterface $logger = null)
    {
        $this->settings = $settings;
        $this->setLogger((null === $logger) ? new NullLogger() : $logger);
    }

    /**
     * Destructor - force to call disconnect
     */
    public function __destruct()
    {
        $this->disconnect();
    }


    /* -----
     * public methods (to override)
     */


    /**
     * Try to connect to the database with the current configured options
     * If connected it will disconnect first
     *
     * @return bool true if the connection was made
     */
    abstract public function connect();

    /**
     * Disconnect
     * @return void
     */
    abstract public function disconnect();

    /**
     * Return the state of the connection
     * @return bool
     */
    abstract public function isConnected();

    /**
     * Get the last inserted id, use it after an insert
     * @return int
     */
    abstract public function lastInsertedID();

    /**
     * start a transaction
     * @return int The transaction level
     */
    abstract public function transBegin();

    /**
     * commit a transaction
     * @return int The transaction level
     */
    abstract public function transCommit();

    /**
     * rollback a transaction
     * @return int The transaction level
     */
    abstract public function transRollback();

    /**
     * Escapes a table name and optionally renames it
     * This function use the prefix setting
     * @param string $tablename
     * @param string $astable
     * @return string
     */
    final public function sqlTable($tablename, $astable = "")
    {
        return $this->sqlTableEscape($this->settings->get('prefix', '') . $tablename, $astable);
    }

    /**
     * Parses a value to secure SQL
     * @param mixed $variable
     * @param string $commontype
     * @param bool $includenull
     * @return string
     */
    abstract public function sqlQuote($variable, $commontype = DBAL::TTEXT, $includenull = false);

    /**
     * Parses a value to secure SQL for IN operator
     * @param array $array
     * @param string $commontype
     * @param bool $includenull
     * @return string example "(1, 3, 5, 7, 11)"
     */
    abstract public function sqlQuoteIn($array, $commontype = DBAL::TTEXT, $includenull = false);

    /**
     * Force to quote as string
     * @param string $variable
     * @return string
     */
    abstract public function sqlString($variable);

    /**
     * Random function
     * @return string
     */
    abstract public function sqlRandomFunc();

    /**
     * Comparison is null
     * @param string $field
     * @param bool $positive If set tu FALSE perform a IS NOT NULL comparison
     * @return string
     */
    abstract public function sqlIsNull($field, $positive = true);

    /**
     * If function
     * @param string $condition
     * @param string $truepart
     * @param string $falsepart
     * @return string
     */
    abstract public function sqlIf($condition, $truepart, $falsepart);

    /**
     * IFNULL function
     * @param string $fieldname
     * @param string $nullvalue
     * @return string
     */
    abstract public function sqlIfNull($fieldname, $nullvalue);

    /**
     * limit function
     * @param string $query The SQL Query to limit
     * @param int $requestedpage
     * @param int $recordsperpage
     * @return string
     */
    abstract public function sqlLimit($query, $requestedpage, $recordsperpage = 20);

    /**
     * like operator (simple)
     * @param string $fieldName
     * @param string $searchString
     * @param bool $wildcardBegin
     * @param bool $wildcardEnd
     * @return string
     */
    abstract public function sqlLike($fieldName, $searchString, $wildcardBegin = true, $wildcardEnd = true);

    /**
     * like operator (advanced)
     * @param string $fieldname
     * @param string $searchstring
     * @param bool $somewords
     * @param string $separator
     * @return string
     */
    final public function sqlLikeSearch($fieldname, $searchstring, $somewords = true, $separator = ' ')
    {
        $return = "";
        if (! is_string($searchstring)) {
            return $return;
        }
        $strings = explode($separator, $searchstring);
        for ($i = 0; $i < count($strings); $i++) {
            if ($strings[$i] != "") {
                $return .= ($return != "" ? (($somewords) ? " OR " : " AND ") : "")
                    . "(" . $this->sqlLike($fieldname, $strings[$i]) . ")";
            }
        }
        return $return;
    }

    /**
     * Concatenation, as this can allow fields and strings, all strings must be previously escaped
     * @param string ...$strings fields and escaped strings
     * @return string
     */
    abstract public function sqlConcatenate(... $strings);


    /**
     * Function to get a part of a date using sql formatting functions
     * Valid part are: YEAR, MONTH, FDOM (First Day Of Month), FYM (Format Year Month),
     * FYMD (Format Year Month Date), DAY, HOUR. MINUTE, SECOND
     * @param string $part
     * @param string $expression
     * @return string
     */
    abstract public function sqlDatePart($part, $expression);


    /* -----
     * protected methods (to override)
     */

    /**
     * Function to escape a table name to not get confused with functions or so
     * @param string $tablename
     * @param string $astable
     * @return string
     */
    abstract protected function sqlTableEscape($tablename, $astable);

    /**
     * Executes a query and return a Result
     * @param string $query
     * @return Result|false
     */
    abstract public function queryResult($query);

    /**
     * Executes a query and return the number of affected rows
     *
     * @param string $query
     * @return integer|false FALSE if the query fails
     */
    abstract protected function queryAffectedRows($query);

    /**
     * Return the last error message, always should return a message
     * @return string
     */
    abstract protected function getLastErrorMessage();

    /* -----
     * public methods (finals, not to override)
     */

    /**
     * Executes a query and return the affected rows
     * @param string $query
     * @return int|bool Number of affected rows or FALSE on error
     */
    final public function execute($query)
    {
        if (false !== $return = $this->queryAffectedRows($query)) {
            $this->logger->info("-- AffectedRows: $return");
        }
        return $return;
    }

    /**
     * Executes a query and return a Result
     * @access protected
     * @param string $query
     * @return Result|false
     */
    final public function query($query)
    {
        return $this->queryResult($query);
    }

    /**
     * Get the first field an( row of a query
     * Returns false if error or empty
     * @param string $query
     * @param mixed $default
     * @return mixed
     */
    final public function queryOne($query, $default = false)
    {
        $return = $default;
        if (false !== $result = $this->query($query)) {
            if (false !== $row = $result->fetchRow()) {
                $keys = array_keys($row);
                $return = $row[$keys[0]];
            }
        }
        return $return;
    }

    /**
     * Get the first row of a query
     * Returns false if error or empty row
     * @param string $query
     * @return array|false
     */
    final public function queryRow($query)
    {
        $return = false;
        if (false !== $result = $this->query($query)) {
            if (false !== $row = $result->fetchRow()) {
                $return = $row;
            }
        }
        return $return;
    }

    /**
     * Get the first row of a query, the values are in common types
     * Returns false if error or empty row
     * @param string $query
     * @return array|false
     */
    final public function queryValues($query)
    {
        $return = false;
        if (false !== $recordset = $this->queryRecordset($query)) {
            if (!$recordset->eof()) {
                $return = $recordset->values;
            }
        }
        return $return;
    }

    /**
     * Get an array of rows of a query
     * Returns false if error
     * @param string $query
     * @return array|false
     */
    final public function queryArray($query)
    {
        $return = false;
        if (false !== $result = $this->query($query)) {
            $return = [];
            while (false !== $row = $result->fetchRow()) {
                $return[] = $row;
            }
        }
        return $return;
    }

    /**
     * Get an array of rows of a query, the values are in common types
     * Returns false if error
     * @param string $query
     * @return array|false
     */
    final public function queryArrayValues($query)
    {
        $return = false;
        if (false !== $recordset = $this->queryRecordset($query)) {
            $return = [];
            while (!$recordset->eof()) {
                $return[] = $recordset->values;
                $recordset->moveNext();
            }
        }
        return $return;
    }

    /**
     * Get an array of rows of a query
     * It uses the keyField as index of the array
     * Returns false if error
     * @param string $query
     * @param string $keyField
     * @param string $keyprefix
     * @return array|false
     */
    final public function queryArrayKey($query, $keyField, $keyprefix = "")
    {
        $return = false;
        if (false !== $result = $this->query($query)) {
            $retarray = [];
            while (false !== $row = $result->fetchRow()) {
                if (!array_key_exists($keyField, $row)) {
                    return false;
                }
                $retarray[strval($keyprefix . $row[$keyField])] = $row;
            }
            $return = $retarray;
        }
        return $return;
    }

    /**
     * Return a one dimenssion array with keys and values defined by keyField and valueField
     * The resulting array keys can have a prefix defined by keyprefix
     * If two keys collapse then the last value will be used
     * Always return an array, even if fail
     * @param string $query
     * @param string $keyField
     * @param string $valueField
     * @param string $keyPrefix
     * @param mixed $default
     * @return array
     */
    final public function queryPairs($query, $keyField, $valueField, $keyPrefix = "", $default = false)
    {
        $return = [];
        if (false != $arr = $this->queryArray($query) and count($arr)) {
            for ($i = 0; $i < count($arr); $i++) {
                $iName = $keyPrefix . (array_key_exists($keyField, $arr[$i]) ? strval($arr[$i][$keyField]) : "");
                $iValue = (array_key_exists($valueField, $arr[$i])) ? $arr[$i][$valueField] : $default;
                $return[$iName] = $iValue;
            }
        }
        return $return;
    }

    /**
     * Return one dimmensional array with the values of one column of the query
     * if the field is not set then the function will take the first column
     * @param string $query
     * @param string $field
     * @return array|false
     */
    final public function queryArrayOne($query, $field = "")
    {
        $return = false;
        if (false !== $result = $this->query($query)) {
            $return = [];
            while (false !== $row = $result->fetchRow()) {
                if ("" === $field) {
                    $keys = array_keys($row);
                    $field = $keys[0];
                }
                $return[] = $row[$field];
            }
        }
        return $return;
    }

    /**
     * Return the result of the query as a imploded string with all the values of the first column
     * @param string $query
     * @param string $default
     * @param string $separator
     * @return string
     */
    final public function queryOnString($query, $default = "", $separator = ", ")
    {
        $return = $default;
        if (false !== $arr = $this->queryArrayOne($query) and count($arr) > 0) {
            $return = implode($separator, $arr);
        }
        return $return;
    }

    /**
     * Returns a Recordset Object from the query, false if error
     * @param string $query
     * @return Recordset
     */
    final public function queryRecordset($query)
    {
        $recordset = new Recordset($this);
        if (! $recordset->query($query)) {
            $this->logger->error("DBAL::queryRecorset failure running $query");
            return false;
        }
        return $recordset;
    }

    /**
     * Get a Pager Object from the query
     * @param string $querySelect
     * @param string $queryCount
     * @param int $page
     * @param int $recordsperpage
     * @return Pager|false
     */
    final public function queryPager($querySelect, $queryCount, $page, $recordsperpage = 20)
    {
        $pager = new Pager($this, $querySelect, $queryCount);
        $pager->setPageSize($recordsperpage);
        $success = ($page == -1) ? $pager->queryAll() : $pager->queryPage($page);
        if (! $success) {
            $this->logger->error("DBAL::queryPager failure running $querySelect");
            return false;
        }
        return $pager;
    }

    /**
     * Get the last error message, false if none
     * @return string
     */
    final public function getLastMessage()
    {
        $return = false;
        if ($this->isConnected()) {
            $strError = $this->getLastErrorMessage();
            if ($strError) {
                $return = $strError;
            }
        }
        return $return;
    }
}
