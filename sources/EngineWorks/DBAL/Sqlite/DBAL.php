<?php namespace EngineWorks\DBAL\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use SQLite3;
use EngineWorks\DBAL\DBAL as AbstractDBAL;

class DBAL extends AbstractDBAL
{
    /**
     * Contains the connection resource for SQLite3
     * @var SQLite3
     */
    protected $sqlite = null;

    /**
     * Contains the transaction level to do nested transactions
     * @var integer
     */
    protected $translevel = 0;

    /**
     * Try to connect to the database with the current configured options
     * It returns true if a connection is made
     * If connected it will disconnect first
     * @return bool
     */
    public function connect()
    {
        // disconnect, this will reset object properties
        $this->disconnect();
        // create the sqlite3 object without error reporting
        $level = error_reporting(0);
        try {
            $this->sqlite = new SQLite3($this->settings->get('filename'), $this->settings->get('flags'));
        } catch (\Exception $ex) {
            $this->logger->info("-- Connection fail");
            $this->logger->error("Cannot create SQLite3 object: " . $ex->getMessage());
            return false;
        } finally {
            error_reporting($level);
        }
        // OK, we are connected
        $this->logger->info("-- Connection success");
        if ($this->settings->get('enable-exceptions', false)) {
            $this->sqlite->enableExceptions(true);
        }
        return true;
    }

    /**
     * Try to disconnect (if connected)
     * This is a procedure, does not return any value
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->logger->info("-- Disconnection");
            $this->sqlite->close();
        }
        $this->translevel = 0;
        $this->sqlite = null;
    }

    /**
     * Return if this object has a valid connection
     * @return bool
     */
    public function isConnected()
    {
        return ($this->sqlite instanceof SQLite3);
    }

    /**
     * Function that returns the last inserted id in the connection, used for auto numeric inserts
     * @return double
     */
    public function lastInsertedID()
    {
        return doubleval($this->sqlite->lastInsertRowID());
    }

    /**
     * force to quote as string
     * @param string $variable
     * @return string
     */
    public function sqlString($variable)
    {
        return SQLite3::escapeString($variable);
    }

    /**
     * Executes a query and return a Result
     * @param string $query
     * @return Result|false
     */
    public function queryResult($query)
    {
        if (false !== $rslt = $this->sqlite->query($query)) {
            return new Result($rslt, -1);
        }
        return false;
    }

    /**
     * Executes a query and return the number of affected rows
     * @param string $query
     * @return integer|false
     */
    protected function queryAffectedRows($query)
    {
        if (false !== $this->sqlite->exec($query)) {
            return $this->sqlite->changes();
        }
        return false;
    }

    /**
     * Get the last error of the connection
     * @return string
     */
    protected function getLastErrorMessage()
    {
        return (($this->isConnected())
            ? "[" . $this->sqlite->lastErrorCode() . "] " . $this->sqlite->lastErrorMsg()
            : "Cannot get the error because there are no active connection");
    }

    /**
     * Function to escape a table name to not get confused with functions or so
     * @param string $tableName
     * @param string $asTable
     * @return string
     */
    protected function sqlTableEscape($tableName, $asTable)
    {
        return '"' . $tableName . '"' . (($asTable) ? " AS " . '"' . $asTable . '"' : "");
    }

    /**
     * @inheritdoc
     */
    public function sqlConcatenate(...$strings)
    {
        if (!count($strings)) {
            return $this->sqlQuote("", CommonTypes::TTEXT);
        }
        return "CONCAT(" . implode(", ", $strings) . ")";
    }


    /**
     * Function to get a part of a date using sql formatting functions
     * Valid part are: YEAR, MONTH, FDOM (First Day Of Month), FYM (Format Year Month),
     * FYMD (Format Year Month Date), DAY, HOUR. MINUTE, SECOND
     * @param string $part
     * @param string $expression
     * @return string
     */
    public function sqlDatePart($part, $expression)
    {
        $format = false;
        $sql = false;
        switch (strtoupper($part)) {
            case "YEAR":
                $format = "%Y";
                break;
            case "MONTH":
                $format = "%m";
                break;
            case "FDOM":
                $format = "%Y-%m-01";
                break;
            case "FYM":
                $format = "%Y-%m";
                break;
            case "FYMD":
                $format = "%Y-%m-%d";
                break;
            case "DAY":
                $format = "%d";
                break;
            case "HOUR":
                $format = "%H";
                break;
            case "MINUTE":
                $format = "%i";
                break;
            case "SECOND":
                $format = "%s";
                break;
        }
        if ($format) {
            $sql = "STRFTIME(" . $expression . ", '" . $format . "')";
        }
        return $sql;
    }

    /**
     * Return the syntax of an IF function
     * @param string $condition
     * @param string $truePart
     * @param string $falsePart
     * @return string
     */
    public function sqlIf($condition, $truePart, $falsePart)
    {
        return "CASE WHEN (" . $condition . ") THEN " . $truePart . " ELSE " . $falsePart;
    }


    /**
     * Compares if expression is null and if its null used other value instead
     * @param string $fieldName
     * @param string $nullValue
     * @return string
     */
    public function sqlIfNull($fieldName, $nullValue)
    {
        return "IFNULL(" . $fieldName . ", " . $nullValue . ")";
    }

    /**
     * Compares if expression is null
     * @param string $fieldValue
     * @param bool $positive
     * @return string
     */
    public function sqlIsNull($fieldValue, $positive = true)
    {
        return $fieldValue . " IS" . ((!$positive) ? " NOT" : "") . " NULL";
    }

    /**
     * Makes a like comparison with wildcards at the begin and end of the string
     * @param string $fieldName
     * @param string $searchString
     * @param bool $wildcardBegin Set if will put a wildcard at the beginning
     * @param bool $wildcardEnd Set if will put a wildcard at the ending
     * @return string
     */
    public function sqlLike($fieldName, $searchString, $wildcardBegin = true, $wildcardEnd = true)
    {
        return $fieldName . " LIKE '"
        . (($wildcardBegin) ? "%" : "") . $this->sqlString($searchString) . (($wildcardEnd) ? "%" : "") . "'";
    }


    /**
     * Transform a SELECT query to be paged
     * By default this functions add a semicolon at the end of the sentence
     * @param string $query
     * @param int $requestedPage
     * @param int $recordsPerPage
     * @return string
     */
    public function sqlLimit($query, $requestedPage, $recordsPerPage = 20)
    {
        $rpp = max(1, $recordsPerPage);
        $query = rtrim($query, "; \t\n\r\0\x0B")
            . " LIMIT " . $this->sqlQuote($rpp * (max(1, $requestedPage) - 1), CommonTypes::TINT)
            . ", " . $this->sqlQuote($rpp, CommonTypes::TINT);
        return $query;
    }

    /**
     * Parses a value to secure SQL
     *
     * @param mixed $variable
     * @param string $commonType
     * @param bool $includeNull
     * @return string
     */
    public function sqlQuote($variable, $commonType = CommonTypes::TTEXT, $includeNull = false)
    {
        if ($includeNull and is_null($variable)) {
            return "NULL";
        }
        // $return = "";
        switch (strtoupper($commonType)) {
            case CommonTypes::TTEXT: // is the most common type, put the case to avoid extra comparisons
                $return = "'" . $this->sqlString($variable) . "'";
                break;
            case CommonTypes::TINT:
                $return = intval(str_replace([",", "$"], "", $variable), 10);
                break;
            case CommonTypes::TNUMBER:
                $return = floatval(str_replace([",", "$"], "", $variable));
                break;
            case CommonTypes::TBOOL:
                $return = ($variable) ? 1 : 0;
                break;
            case CommonTypes::TDATE:
                $return = "'" . date("Y-m-d", $variable) . "'";
                break;
            case CommonTypes::TTIME:
                $return = "'" . date("H:i:s", $variable) . "'";
                break;
            case CommonTypes::TDATETIME:
                $return = "'" . date("Y-m-d H:i:s", $variable) . "'";
                break;
            default:
                $return = "'" . $this->sqlString($variable) . "'";
        }
        return strval($return);
    }

    /**
     * Return the random function
     * @return string
     */
    public function sqlRandomFunc()
    {
        return "RANDOM()";
    }


    /**
     * @inheritdoc
     */
    public function transBegin()
    {
        $this->logger->info("-- TRANSACTION BEGIN");
        $this->translevel++;
        if ($this->translevel != 1) {
            $this->logger->info("-- BEGIN (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute("BEGIN TRANSACTION");
        }
    }

    /**
     * Commit a transaction, if the commit was not executed then it return FALSE
     * this could happen because of nested transactions
     * @return bool
     */
    public function transCommit()
    {
        $this->logger->info("-- TRANSACTION COMMIT");
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- COMMIT (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute("COMMIT");
            return true;
        }
        return false;
    }

    /**
     * Rollback a transaction, if the rollback is out of sync it return false
     * this could happen because of nested transactions
     * @return bool
     */
    public function transRollback()
    {
        $this->logger->info("-- TRANSACTION ROLLBACK ");
        $this->execute("ROLLBACK");
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- ROLLBACK (this rollback is out of sync) [{$this->translevel}]");
            return false;
        }
        return true;
    }
}
