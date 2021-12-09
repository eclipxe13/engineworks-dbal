<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Exceptions;

use EngineWorks\DBAL\Exceptions\QueryException;
use Exception;
use PHPUnit\Framework\TestCase;

final class QueryExceptionTest extends TestCase
{
    public function testProperties(): void
    {
        $query = 'query';
        $message = 'message';
        $code = 9;
        $previous = new Exception();

        $exception = new QueryException($message, $query, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($query, $exception->getQuery());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
