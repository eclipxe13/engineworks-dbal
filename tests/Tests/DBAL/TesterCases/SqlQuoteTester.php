<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\TesterCases;

use EngineWorks\DBAL\DBAL;
use EngineWorks\DBAL\Tests\Utils\ExampleEnum;
use EngineWorks\DBAL\Tests\Utils\ExampleIntegerBackedEnum;
use EngineWorks\DBAL\Tests\Utils\ExampleStringBackedEnum;
use EngineWorks\DBAL\Tests\WithDbalTestCase;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class SqlQuoteTester
{
    /** @var TestCase */
    private $test;

    /** @var DBAL */
    private $dbal;

    /** @var string */
    private $expectedSingleQuote = "''''";

    /** @var string */
    private $expectedDoubleQuote = "'\"'";

    public function __construct(
        WithDbalTestCase $test,
        string $expectedSingleQuote = '',
        string $expectedDoubleQuote = ''
    ) {
        $this->test = $test;
        $this->dbal = $test->getDbal();
        if ('' !== $expectedSingleQuote) {
            $this->expectedSingleQuote = $expectedSingleQuote;
        }
        if ('' !== $expectedDoubleQuote) {
            $this->expectedDoubleQuote = $expectedDoubleQuote;
        }
    }

    public function execute(): void
    {
        foreach ($this->providerSqlQuote() as $label => $arguments) {
            $this->testSqlQuote($label, ...$arguments);
        }
        foreach ($this->providerSqlQuoteWithLocale() as $arguments) {
            $this->testSqlQuoteWithLocale(...$arguments);
        }
        $this->testWithInvalidCommonType();
        $this->testWithEnum();
    }

    /** @return array<string, array{string, scalar|object|null, string, bool}> */
    public function providerSqlQuote(): array
    {
        $timestamp = mktime(23, 31, 59, 12, 31, 2016);
        $date = '2016-12-31';
        $time = '23:31:59';
        $datetime = "$date $time";
        $xmlValue = (new SimpleXMLElement(/** @lang xml */ '<d v="55.1"/>'))['v'];
        return [
            // texts
            'text normal' => ["'foo'", 'foo', DBAL::TTEXT, false],
            'text normal include null' => ["'foo'", 'foo', DBAL::TTEXT, true],
            'text zero' => ["'0'", 0, DBAL::TTEXT, false],
            'text integer' => ["'9'", 9, DBAL::TTEXT, false],
            'text float' => ["'1.2'", 1.2, DBAL::TTEXT, false],
            'text multibyte' => ["'á é í ó ú'", 'á é í ó ú', DBAL::TTEXT, false],
            // integer
            'integer normal' => ['9', 9, DBAL::TINT, false],
            'integer float' => ['1', 1.2, DBAL::TINT, false],
            'integer text not numeric' => ['0', 'foo bar', DBAL::TINT, false],
            'integer text numeric simple' => ['987', '987', DBAL::TINT, false],
            'integer text numeric complex' => ['-1234', '- $ 1,234.56', DBAL::TINT, false],
            'integer empty' => ['0', '', DBAL::TINT, false],
            'integer whitespace' => ['0', ' ', DBAL::TINT, false],
            'integer bool false' => ['0', false, DBAL::TINT, false],
            'integer bool true' => ['1', true, DBAL::TINT, false],
            // float
            'float normal' => ['9.1', 9.1, DBAL::TNUMBER, false],
            'float int' => ['8', 8, DBAL::TNUMBER, false],
            'float text not numeric' => ['0', 'foo bar', DBAL::TNUMBER, false],
            'float text numeric simple' => ['987.654', '987.654', DBAL::TNUMBER, false],
            'float text numeric complex' => ['-1234.56789', "- $\t1,234.567,89", DBAL::TNUMBER, false],
            'float empty' => ['0', '', DBAL::TNUMBER, false],
            'float whitespace' => ['0', ' ', DBAL::TNUMBER, false],
            // bool
            'bool normal false' => ['0', false, DBAL::TBOOL, false],
            'bool equal false' => ['0', '0', DBAL::TBOOL, false],
            'bool normal true' => ['1', true, DBAL::TBOOL, false],
            'bool equal true' => ['1', 'foo', DBAL::TBOOL, false],
            // date time datetime
            'date normal' => ["'$date'", $timestamp, DBAL::TDATE, false],
            'time normal' => ["'$time'", $timestamp, DBAL::TTIME, false],
            'datetime normal' => ["'$datetime'", $timestamp, DBAL::TDATETIME, false],
            //
            // nulls
            //
            'null text' => ['NULL', null, DBAL::TTEXT, true],
            'null int' => ['NULL', null, DBAL::TINT, true],
            'null float' => ['NULL', null, DBAL::TNUMBER, true],
            'null bool' => ['NULL', null, DBAL::TBOOL, true],
            'null date' => ['NULL', null, DBAL::TDATE, true],
            'null time' => ['NULL', null, DBAL::TTIME, true],
            'null datetime' => ['NULL', null, DBAL::TDATETIME, true],
            'null text notnull' => ["''", null, DBAL::TTEXT, false],
            'null int notnull' => ['0', null, DBAL::TINT, false],
            'null float notnull' => ['0', null, DBAL::TNUMBER, false],
            'null bool notnull' => ['0', null, DBAL::TBOOL, false],
            'null date notnull' => ["'1970-01-01'", null, DBAL::TDATE, false],
            'null time notnull' => ["'00:00:00'", null, DBAL::TTIME, false],
            'null datetime notnull' => ["'1970-01-01 00:00:00'", null, DBAL::TDATETIME, false],
            // special chars
            "special char '" => [$this->expectedSingleQuote, "'", DBAL::TTEXT, false],
            'special char \"' => [$this->expectedDoubleQuote, '"', DBAL::TTEXT, false],
            // object
            'object to string' => ["'55.1'", $xmlValue, DBAL::TTEXT, true],
            'object to string not null' => ["'55.1'", $xmlValue, DBAL::TTEXT, false],
            'object to int' => ['55', $xmlValue, DBAL::TINT, true],
            'object to int not null' => ['55', $xmlValue, DBAL::TINT, false],
            'object to number' => ['55.1', $xmlValue, DBAL::TNUMBER, true],
            'object to number not null' => ['55.1', $xmlValue, DBAL::TNUMBER, false],
        ];
    }

    /**
     * @param string $label
     * @param string $expected
     * @param mixed $value
     * @param string $type
     * @param bool $includeNull
     */
    public function testSqlQuote(string $label, string $expected, $value, string $type, bool $includeNull): void
    {
        $this->test->assertSame(
            $expected,
            $this->dbal->sqlQuote($value, $type, $includeNull),
            "sqlQuote fail testing $label"
        );
    }

    /** @return array<string, array{string, string, string}> */
    public function providerSqlQuoteWithLocale(): array
    {
        return [
            'en_US' => ['en_US', '-1234.56789', "- $\t1,234.567,89 "],
            'en_US.utf-8' => ['en_US.utf-8', '-1234.56789', "- $\t1,234.567,89 "],
            'pt_BR' => ['pt_BR', '-1234.56789', "- R$\t1.234,567.89 "],
        ];
    }

    public function testSqlQuoteWithLocale(string $locale, string $expected, string $value): void
    {
        $currentNumeric = strval(setlocale(LC_NUMERIC, '0'));
        $currentMonetary = strval(setlocale(LC_MONETARY, '0'));

        // mark skipped if not found
        if (false === setlocale(LC_NUMERIC, $locale) || false === setlocale(LC_MONETARY, $locale)) {
            setlocale(LC_NUMERIC, $currentNumeric);
            setlocale(LC_MONETARY, $currentMonetary);
            $this->test->markTestSkipped("Cannot setup locale '$locale'");
        }

        // the test
        $this->test->assertSame($expected, $this->dbal->sqlQuote($value, DBAL::TNUMBER, false));

        // anyhow, restore the previous locale
        setlocale(LC_NUMERIC, $currentNumeric);
        setlocale(LC_MONETARY, $currentMonetary);
    }

    public function testWithInvalidCommonType(): void
    {
        $this->test->assertSame("'Ñu'", $this->dbal->sqlQuote('Ñu', 'NON-EXISTENT-COMMONTYPE'));
    }

    public function testWithEnum(): void
    {
        if (PHP_VERSION_ID < 80100) { // PHP 8.1
            return;
        }
        // Regular Enum
        $unitEnum = ExampleEnum::Foo;
        $expectedQuotedValue = $this->dbal->sqlQuote($unitEnum->name, DBAL::TTEXT);
        $this->test->assertSame($expectedQuotedValue, $this->dbal->sqlQuote($unitEnum, DBAL::TTEXT));

        // String Enum
        $backedStringEnum = ExampleStringBackedEnum::Foo;
        $expectedQuotedValue = $this->dbal->sqlQuote($backedStringEnum->value, DBAL::TTEXT);
        $this->test->assertSame($expectedQuotedValue, $this->dbal->sqlQuote($backedStringEnum, DBAL::TTEXT));

        // Integer Enum
        $backedIntegerEnum = ExampleIntegerBackedEnum::Foo;
        $expectedQuotedValue = $this->dbal->sqlQuote($backedIntegerEnum->value, DBAL::TINT);
        $this->test->assertSame($expectedQuotedValue, $this->dbal->sqlQuote($backedIntegerEnum, DBAL::TINT));
    }
}
