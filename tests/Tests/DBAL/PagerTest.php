<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL;

use EngineWorks\DBAL\Pager;
use EngineWorks\DBAL\Recordset;
use EngineWorks\DBAL\Tests\SqliteWithDatabaseTestCase;

class PagerTest extends SqliteWithDatabaseTestCase
{
    public function testPagerWithCountQuery(): void
    {
        $sql = 'SELECT * FROM albums;';
        $sqlCount = 'SELECT COUNT(*) FROM albums;';
        $pager = new Pager($this->dbal, $sql, $sqlCount);

        $this->assertSame(Pager::COUNT_METHOD_QUERY, $pager->getCountMethod());
        $this->checkPagerStatus($pager);
    }

    public function testPagerWithSelect(): void
    {
        $sql = 'SELECT * FROM albums;';
        $pager = new Pager($this->dbal, $sql);

        $this->assertSame(Pager::COUNT_METHOD_SELECT, $pager->getCountMethod());
        $this->checkPagerStatus($pager);
    }

    public function testPagerWithRecordcount(): void
    {
        $sql = 'SELECT * FROM albums;';
        $pager = new Pager($this->dbal, $sql);

        $pager->setCountMethod(Pager::COUNT_METHOD_RECORDCOUNT);
        $this->assertSame(Pager::COUNT_METHOD_RECORDCOUNT, $pager->getCountMethod());
    }

    public function testSetCountMethod(): void
    {
        $sql = 'SELECT * FROM albums;';
        $pager = new Pager($this->dbal, $sql);

        $pager->setCountMethod(Pager::COUNT_METHOD_RECORDCOUNT);
        $this->assertSame(Pager::COUNT_METHOD_RECORDCOUNT, $pager->getCountMethod());

        $pager->setCountMethod(Pager::COUNT_METHOD_SELECT);
        $this->assertSame(Pager::COUNT_METHOD_SELECT, $pager->getCountMethod());

        $this->expectException(\InvalidArgumentException::class);
        $pager->setCountMethod(Pager::COUNT_METHOD_QUERY);
    }

    public function testSetPageSize(): void
    {
        $sql = 'SELECT * FROM albums;';
        $pager = new Pager($this->dbal, $sql);

        $this->assertSame(20, $pager->getPageSize(), 'The default page size is not 20');

        $pager->setPageSize(100);
        $this->assertSame(100, $pager->getPageSize());

        // min is 1
        $pager->setPageSize(0);
        $this->assertSame(1, $pager->getPageSize());

        $pager->setPageSize(-100);
        $this->assertSame(1, $pager->getPageSize());
    }

    public function checkPagerStatus(Pager $pager): void
    {
        $this->assertEquals(20, $pager->getPageSize());
        $this->assertSame(45, $pager->getTotalCount());

        // this methods require to run queryPage first
        $this->assertTrue($pager->queryPage(2));
        $this->assertEquals(2, $pager->getPage());
        $this->assertEquals(3, $pager->getTotalPages());
        $this->assertInstanceOf(Recordset::class, $pager->getRecordset());
    }
}
