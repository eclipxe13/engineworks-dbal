<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\DBAL\Sample;

use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    /** @var array */
    private $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $this->logs[$level][] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    public function retrieve($level)
    {
        return (array_key_exists($level, $this->logs)) ? $this->logs[$level] : [];
    }

    public function messages($level, $addLevelPrefix = false)
    {
        $list = $this->retrieve($level);
        $return = [];
        foreach ($list as $element) {
            $return[] = ($addLevelPrefix ? $level . ': ' : '') . $element['message'];
        }
        return $return;
    }

    public function allMessages()
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

    public function lastMessage($level): string
    {
        $list = $this->retrieve($level);
        $count = count($list);
        return (0 == $count) ? '' : $list[$count - 1]['message'];
    }
}
