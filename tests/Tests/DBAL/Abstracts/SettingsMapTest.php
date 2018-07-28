<?php
namespace EngineWorks\DBAL\Tests\DBAL\Abstracts;

use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\DBAL\Sample\SettingsMapExtension;
use PHPUnit\Framework\TestCase;

class SettingsMapTest extends TestCase
{
    public function testConstructor()
    {
        $settings = new SettingsMapExtension();
        $this->assertInstanceOf(Settings::class, $settings);
    }

    public function testConstructorWithValues()
    {
        $expected = ['foo' => 123, 'bar' => true];
        $settings = new SettingsMapExtension(['foo' => 123]);
        $this->assertSame($expected, $settings->all());
    }

    public function testConstructorWithAdditionalValues()
    {
        $expected = ['foo' => null, 'bar' => true];
        $settings = new SettingsMapExtension(['baz' => 'baz']);
        $this->assertSame($expected, $settings->all());
    }

    public function testSetAllWithNormalValues()
    {
        $expected = ['foo' => 123, 'bar' => true];
        $settings = new SettingsMapExtension();
        $settings->setAll(['foo' => 123]);
        $this->assertSame($expected, $settings->all());
    }

    public function testSetAllWithAdditionalValues()
    {
        $expected = ['foo' => null, 'bar' => true];
        $settings = new SettingsMapExtension();
        $settings->setAll(['baz' => 'baz']);
        $this->assertSame($expected, $settings->all());
    }

    public function testSet()
    {
        $settings = new SettingsMapExtension();
        $settings->set('foo', 123);
        $this->assertSame(123, $settings->get('foo'));
    }

    public function testSetThrowExceptionWithNonExistentKey()
    {
        $settings = new SettingsMapExtension();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Setting baz does not exists');
        $settings->set('baz', 123);
    }

    public function testGetValue()
    {
        $settings = new SettingsMapExtension();
        $this->assertTrue($settings->get('bar'));

        $this->assertNull($settings->get('baz'));
        $this->assertSame(1, $settings->get('baz', 1));
        $settings->set('foo', 2);
        $this->assertSame(2, $settings->get('foo', 1));
    }

    public function testExists()
    {
        $settings = new SettingsMapExtension();
        $this->assertTrue($settings->exists('foo'));
        $this->assertFalse($settings->exists('baz'));
    }
}
