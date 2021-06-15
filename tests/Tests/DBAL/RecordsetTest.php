<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Tests\SqliteWithDatabaseTestCase;

class RecordsetTest extends SqliteWithDatabaseTestCase
{
    public function testValuesHadChangedWithStringZeros(): void
    {
        $dbal = $this->dbal;

        $sql = 'UPDATE albums SET title = ' . $dbal->sqlQuote('00000', CommonTypes::TTEXT) . ' WHERE (albumid = 1);';
        $dbal->execute($sql);

        $sql = 'SELECT * FROM albums WHERE (albumid = 1);';
        $recordset = $dbal->createRecordset($sql);
        $this->assertSame('00000', $recordset->values['title']);
        $this->assertFalse($recordset->valuesHadChanged());

        $recordset->values['title'] = '0';
        $this->assertTrue($recordset->valuesHadChanged());
    }

    public function testRecordsetUsesDbalLoggerWhenNull(): void
    {
        $logger = new NullLogger();
        $recordset = new Recordset($this->dbal);
        $this->assertSame($this->dbal->getLogger(), $recordset->getLogger());

        $recordset->setLogger($logger);
        $this->assertSame($logger, $recordset->getLogger());

        $recordset->setLogger(null);
        $this->assertSame($this->dbal->getLogger(), $recordset->getLogger());
    }
}
