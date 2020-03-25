<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sqlsrv;

use EngineWorks\DBAL\Tests\DBAL\TesterTraits\MsSqlServerNoCountSettingTrait;
use EngineWorks\DBAL\Tests\SqlsrvWithDatabaseTestCase;

class SqlsrvDbalNoCountSettingTest extends SqlsrvWithDatabaseTestCase
{
    use MsSqlServerNoCountSettingTrait;
}
