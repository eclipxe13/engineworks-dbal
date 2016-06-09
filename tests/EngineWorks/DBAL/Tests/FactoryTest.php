<?php namespace EngineWorks\DBAL\Tests;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Factory;
use EngineWorks\DBAL\Settings;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryCreateValidSqlite()
    {
        $namespace = 'EngineWorks\DBAL\Sqlite';
        $factory = new Factory($namespace);

        $settings = $factory->settings();
        $this->assertNotNull($settings);

        $dbal = $factory->dbal($settings);
        $this->assertNotNull($dbal);
    }
    
    public function testSettingWhenClassDoesNotExists()
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $settingsname = 'SettingsClass';
        $factory = new Factory($namespace, 'X', $settingsname);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\$settingsname does not exists");

        $factory->settings();
    }

    public function testSettingWhenClassDoesNotImplementsInterface()
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $factory = new Factory($namespace, '', 'EmptyObject');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\EmptyObject does not implements " . Settings::class);

        $factory->settings();
    }

    public function testDbalWhenClassDoesNotExists()
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $dbalname = 'SettingsClass';
        $factory = new Factory($namespace, $dbalname, 'X');
        /** @var Settings $mockSettings */
        $mockSettings = $this->getMock(Settings::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\$dbalname does not exists");

        $factory->dbal($mockSettings);
    }

    public function testDbalWhenClassDoesNotExtendsDbal()
    {
        $namespace = __NAMESPACE__ . '\Sample';
        $dbalname = 'EmptyObject';
        $factory = new Factory($namespace, $dbalname, 'X');
        /** @var Settings $mockSettings */
        $mockSettings = $this->getMock(Settings::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Class $namespace\\$dbalname does not extends " . DBAL::class);

        $factory->dbal($mockSettings);
    }
}
