<?php
namespace EngineWorks\DBAL\Exceptions;

use Throwable;

class QueryException extends \RuntimeException
{
    /** @var string */
    private $query;

    public function __construct(string $query = '', string $prefix = null, int $code = 0, Throwable $previous = null)
    {
        if (null === $prefix) {
            $prefix = 'Unable to perform query';
        }
        parent::__construct("$prefix: $query", $code, $previous);
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
