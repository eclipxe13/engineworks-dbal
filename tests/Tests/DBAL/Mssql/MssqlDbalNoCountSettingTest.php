<?php
namespace EngineWorks\DBAL\Tests\DBAL\Mssql;

use EngineWorks\DBAL\Tests\DBAL\TesterTraits\MsSqlServerNoCountSettingTrait;
use EngineWorks\DBAL\Tests\MssqlWithDatabaseTestCase;

class MssqlDbalNoCountSettingTest extends MssqlWithDatabaseTestCase
{
    use MsSqlServerNoCountSettingTrait;
}
