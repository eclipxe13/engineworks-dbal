<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sample;

use InvalidArgumentException;
use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    /** @var array<string, string[]> */
    private $logs = [];

    public function log($level, $message, array $context = []): void
    {
        if (! is_string($level)) {
            throw new InvalidArgumentException('Invalid argument level, expected string');
        }
        $this->logs[$level][] = $message;
    }

    /** @return string[] */
    public function retrieve(string $level): array
    {
        return $this->logs[$level] ?? [];
    }

    /** @return string[] */
    public function messages(string $level, bool $addLevelPrefix = false): array
    {
        $list = $this->retrieve($level);
        if (! $addLevelPrefix) {
            return $list;
        }
        return array_map(function (string $message) use ($level): string {
            return $level . ': ' . $message;
        }, $list);
    }

    /** @return string[] */
    public function allMessages(): array
    {
        $return = [];
        foreach (array_keys($this->logs) as $level) {
            $return = array_merge($return, $this->messages($level, true));
        }
        return $return;
    }

    public function clear(): void
    {
        $this->logs = [];
    }

    public function lastMessage(string $level): string
    {
        $list = $this->retrieve($level);
        if ([] === $list) {
            return '';
        }
        return $list[count($list) - 1];
    }
}
