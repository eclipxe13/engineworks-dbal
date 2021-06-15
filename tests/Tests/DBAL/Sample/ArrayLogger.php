<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sample;

use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    /** @var array<string, array<int, array>> */
    private $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $this->logs[$level][] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    /** @return array<int, mixed> */
    public function retrieve(string $level): array
    {
        return (array_key_exists($level, $this->logs)) ? $this->logs[$level] : [];
    }

    /** @return string[] */
    public function messages(string $level, bool $addLevelPrefix = false): array
    {
        $list = $this->retrieve($level);
        $return = [];
        foreach ($list as $element) {
            $return[] = ($addLevelPrefix ? $level . ': ' : '') . $element['message'];
        }
        return $return;
    }

    /** @return string[] */
    public function allMessages(): array
    {
        $return = [];
        foreach (array_keys($this->logs) as $level) {
            $list = $this->messages($level);
            foreach ($list as $message) {
                $return[] = $level . ': ' . $message;
            }
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
        $lastIndex = array_key_last($list);
        if (null === $lastIndex) {
            return '';
        }
        return $list[$lastIndex]['message'];
    }
}
