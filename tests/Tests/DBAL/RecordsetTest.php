<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Tests\SqliteWithDatabaseTestCase;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

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

    public function testQueryThrowsExceptionWhenDbalIsNotConnected(): void
    {
        /** @var DBAL&MockObject $dbal */
        $dbal = $this->createMock(DBAL::class);
        $dbal->method('isConnected')->willReturn(false);
        $dbal->method('connect')->willReturn(false);

        $recordset = new Recordset($dbal);
        try {
            $recordset->query('SELECT @@VERSION');
        } catch (LogicException $exception) {
            $this->assertStringContainsString(
                'object does not have a connected DBAL',
                $exception->getMessage()
            );
        }
    }

    public function testLastInsertedIdCallsDbal(): void
    {
        $expected = 999;
        /** @var DBAL&MockObject $dbal */
        $dbal = $this->createMock(DBAL::class);
        $dbal->expects($this->once())->method('lastInsertedID')->willReturn($expected);

        $recordset = new Recordset($dbal);
        $this->assertSame($expected, $recordset->lastInsertedID());
    }

    public function testDbalCreateRecordsetUsesDbalLogger(): void
    {
        $recordset = $this->dbal->createRecordset('SELECT 1');
        $this->assertSame($recordset->getLogger(), $this->dbal->getLogger());
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
