<?php
namespace EngineWorks\DBAL\Tests\DBAL\TesterTraits;

/** @var $this \EngineWorks\DBAL\Tests\WithDatabaseTestCase */
trait MsSqlServerNoCountSettingTrait
{
    protected $heavyNumCount = 5000;

    protected function setUp()
    {
        parent::setUp();
        $this->executeStatement(
            '
            CREATE PROCEDURE ExpensiveTest @numRecords INT AS
            BEGIN
                DECLARE @current INT;
                SET @current = 0;
            
                BEGIN TRANSACTION;
                DELETE FROM ExpensiveTable;
                BEGIN TRY
                    WHILE (@current < @numRecords)
                    BEGIN
                        SET @current = @current + 1;
                        INSERT INTO ExpensiveTable VALUES (@current, CONVERT(nvarchar(200), NEWID()));
                    END
                END TRY
                BEGIN CATCH
                    ROLLBACK TRANSACTION;
                END CATCH
                IF @@TRANCOUNT > 0 COMMIT TRANSACTION;
            END;
            '
        );
        $this->executeStatement('CREATE ' . 'TABLE ExpensiveTable (pos integer NOT NULL, data nvarchar(200));');
    }

    protected function tearDown()
    {
        $this->executeStatement(
            '
            DROP PROCEDURE ExpensiveTest;
            DROP TABLE ExpensiveTable;
            '
        );
        parent::tearDown();
    }

    private function queryRecordCount(): int
    {
        return (int) $this->getDbal()->queryOne('SELECT COUNT(*) FROM ExpensiveTable;');
    }

    public function testExecuteWithOne()
    {
        $execReturn = $this->dbal->execute('EXEC ExpensiveTest 1;');
        $this->assertSame(0, $execReturn, 'EXEC was expected to return -1');
        $this->assertSame(1, $this->queryRecordCount(), 'EXEC did not insert 1 record');
    }

    public function testExecuteWithOutNoCount()
    {
        $numRows = $this->heavyNumCount;
        $execReturn = $this->dbal->execute("EXEC ExpensiveTest $numRows;");
        $this->assertSame(0, $execReturn, 'EXEC was expected to return -1');
        $this->assertLessThanOrEqual(
            $numRows,
            $this->queryRecordCount(),
            "EXEC did not insert less or equal than $numRows records without including SET NOCOUNT"
        );
    }

    public function testExecuteWithNoCountOff()
    {
        $numRows = $this->heavyNumCount;
        $this->dbal->execute('SET NOCOUNT OFF;');
        $execReturn = $this->dbal->execute("EXEC ExpensiveTest $numRows;");
        $this->assertSame(0, $execReturn, 'EXEC was expected to return -1');
        $this->assertLessThanOrEqual(
            $numRows,
            $this->queryRecordCount(),
            "EXEC did not insert less or equal than $numRows records with SET NOCOUNT OFF"
        );
    }

    public function testExecuteWithNoCountOn()
    {
        $numRows = $this->heavyNumCount;
        $this->dbal->execute('SET NOCOUNT ON;');
        $execReturn = $this->dbal->execute("EXEC ExpensiveTest $numRows;");
        $this->assertSame(0, $execReturn, 'EXEC was expected to return -1');
        $this->assertSame(
            $numRows,
            $this->queryRecordCount(),
            "EXEC did not insert $numRows records with SET NOCOUNT ON"
        );
    }

    public function testDeleteWithoutSetCount()
    {
        $numRows = 10;
        $this->dbal->execute("EXEC ExpensiveTest $numRows;");
        $this->assertSame($numRows, $this->dbal->execute('DELETE FROM ExpensiveTable;'));
    }

    public function testDeleteWithSetCountOn()
    {
        $numRows = 10;
        $this->dbal->execute("SET NOCOUNT ON; EXEC ExpensiveTest $numRows;");
        $this->assertSame(0, $this->dbal->execute('DELETE FROM ExpensiveTable;'));
    }

    public function testDeleteWithSetCountOff()
    {
        $numRows = 10;
        $this->dbal->execute("SET NOCOUNT OFF; EXEC ExpensiveTest $numRows;");
        $this->assertSame($numRows, $this->dbal->execute('DELETE FROM ExpensiveTable;'));
    }

    public function testDeleteWithSetCountOnAndOff()
    {
        $numRows = 10;
        $this->dbal->execute("SET NOCOUNT ON; EXEC ExpensiveTest $numRows; SET NOCOUNT OFF;");
        $this->assertSame($numRows, $this->dbal->execute('DELETE FROM ExpensiveTable;'));
    }
}
