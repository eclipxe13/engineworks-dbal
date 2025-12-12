<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace EngineWorks\DBAL\Mssql;

use EngineWorks\DBAL\Internal\SqlServerResultFields;
use EngineWorks\DBAL\Result as ResultInterface;
use EngineWorks\DBAL\Traits\ResultImplementsCountable;
use EngineWorks\DBAL\Traits\ResultImplementsIterator;
use PDO;
use PDOStatement;

class Result implements ResultInterface
{
    use ResultImplementsCountable;
    use ResultImplementsIterator;

    /**
     * PDO element
     * @var PDOStatement<mixed>
     */
    private $stmt;

    /**
     * The number of the result rows
     * @var int<0, max>
     */
    private $numRows;

    /**
     * Set of fieldname and commontype to use instead of detectedTypes
     * @var array<string, string>
     */
    private $overrideTypes;

    /**
     * The place where getFields result is cached
     * @var array<int, array{name: string, table: string, commontype: string}>|null
     */
    private $cachedGetFields;

    /**
     * Result based on PDOStatement
     *
     * @param PDOStatement<mixed> $result
     * @param int $numRows If negative number then the number of rows will be obtained
     * from fetching all the rows and move first
     * @param array<string, string> $overrideTypes
     */
    public function __construct(PDOStatement $result, int $numRows, array $overrideTypes = [])
    {
        $this->stmt = $result;
        $this->overrideTypes = $overrideTypes;
        $this->numRows = ($numRows < 0) ? $this->obtainNumRows() : $numRows;
    }

    /**
     * Close the query and remove property association
     */
    public function __destruct()
    {
        $this->stmt->closeCursor();
    }

    /**
     * Internal method to retrieve the number of rows if not supplied from constructor
     * @return int<0, max>
     */
    private function obtainNumRows(): int
    {
        $count = 0;
        while (false !== $this->stmt->fetch(PDO::FETCH_NUM)) {
            $count = $count + 1;
        }
        $this->stmt->execute();
        return $count;
    }

    public function getFields(): array
    {
        if (null === $this->cachedGetFields) {
            $typeChecker = new SqlServerResultFields($this->overrideTypes, 'native_type');
            $this->cachedGetFields = $typeChecker->obtainFields($this->stmt);
        }

        return $this->cachedGetFields;
    }

    public function getIdFields(): bool
    {
        return false;
    }

    public function resultCount(): int
    {
        return $this->numRows;
    }

    public function fetchRow()
    {
        /** @phpstan-var array<string, scalar|null>|false $return */
        $return = $this->stmt->fetch(PDO::FETCH_ASSOC);
        return (! is_array($return)) ? false : $return;
    }

    public function moveTo(int $offset): bool
    {
        // there are no records
        if ($this->resultCount() <= 0) {
            return false;
        }
        // the offset is out of bounds
        if ($offset < 0 || $offset > $this->resultCount() - 1) {
            return false;
        }
        // if the offset is on previous
        if (! $this->moveFirst()) {
            return false;
        }
        // move to the offset
        for ($i = 0; $i < $offset; $i++) {
            if (false === $this->stmt->fetch(PDO::FETCH_NUM)) {
                return false;
            }
        }
        return true;
    }

    public function moveFirst(): bool
    {
        if ($this->resultCount() <= 0) {
            return false;
        }
        return false !== $this->stmt->execute();
    }
}
