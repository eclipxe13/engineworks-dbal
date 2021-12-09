<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Abstracts;

use EngineWorks\DBAL\Settings;
use EngineWorks\DBAL\Tests\DBAL\Sample\SettingsMapExtension;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SettingsMapTest extends TestCase
{
    public function testConstructor(): void
    {
        $settings = new SettingsMapExtension();
        $this->assertInstanceOf(Settings::class, $settings);
    }

    public function testConstructorWithValues(): void
    {
        $expected = ['foo' => 123, 'bar' => true];
        $settings = new SettingsMapExtension(['foo' => 123]);
        $this->assertSame($expected, $settings->all());
    }

    public function testConstructorWithAdditionalValues(): void
    {
        $expected = ['foo' => null, 'bar' => true];
        $settings = new SettingsMapExtension(['baz' => 'baz']);
        $this->assertSame($expected, $settings->all());
    }

    public function testSetAllWithNormalValues(): void
    {
        $expected = ['foo' => 123, 'bar' => true];
        $settings = new SettingsMapExtension();
        $settings->setAll(['foo' => 123]);
        $this->assertSame($expected, $settings->all());
    }

    public function testSetAllWithAdditionalValues(): void
    {
        $expected = ['foo' => null, 'bar' => true];
        $settings = new SettingsMapExtension();
        $settings->setAll(['baz' => 'baz']);
        $this->assertSame($expected, $settings->all());
    }

    public function testSet(): void
    {
        $settings = new SettingsMapExtension();
        $settings->set('foo', 123);
        $this->assertSame(123, $settings->get('foo'));
    }

    public function testSetThrowExceptionWithNonExistentKey(): void
    {
        $settings = new SettingsMapExtension();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Setting baz does not exists');
        $settings->set('baz', 123);
    }

    public function testGetValue(): void
    {
        $settings = new SettingsMapExtension();
        $this->assertTrue($settings->get('bar'));

        $this->assertNull($settings->get('baz'));
        $this->assertSame(1, $settings->get('baz', 1));
        $settings->set('foo', 2);
        $this->assertSame(2, $settings->get('foo', 1));
    }

    public function testExists(): void
    {
        $settings = new SettingsMapExtension();
        $this->assertTrue($settings->exists('foo'));
        $this->assertFalse($settings->exists('baz'));
    }
}
