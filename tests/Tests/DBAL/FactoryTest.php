<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testFactoryCreateValidSqlite(): void
    {
        $namespace = 'EngineWorks\DBAL\Sqlite';
        $factory = new Factory($namespace);

        $settings = $factory->settings();
        $this->assertNotNull($settings);

        $dbal = $factory->dbal($settings);
        $this->assertNotNull($dbal);
    }

    public function testSettingWhenClassDoesNotExists(): void
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $settingsname = 'SettingsClass';
        $factory = new Factory($namespace, 'X', $settingsname);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\$settingsname does not exists");

        $factory->settings();
    }

    public function testSettingWhenClassDoesNotImplementsInterface(): void
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $factory = new Factory($namespace, '', 'EmptyObject');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\EmptyObject does not implements " . Settings::class);

        $factory->settings();
    }

    public function testDbalWhenClassDoesNotExists(): void
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $dbalname = 'SettingsClass';
        $factory = new Factory($namespace, $dbalname, 'X');
        /** @var Settings $mockSettings */
        $mockSettings = $this->createMock(Settings::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\$dbalname does not exists");

        $factory->dbal($mockSettings);
    }

    public function testDbalWhenClassDoesNotImplementsDbal(): void
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $dbalname = 'EmptyObject';
        $factory = new Factory($namespace, $dbalname, 'X');
        /** @var Settings $mockSettings */
        $mockSettings = $this->createMock(Settings::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\$dbalname does not implements " . DBAL::class);

        $factory->dbal($mockSettings);
    }
}
