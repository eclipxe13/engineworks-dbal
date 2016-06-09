<?php namespace EngineWorks\DBAL\Tests\Sample;

use Psr\Log\LoggerInterface;

class ArrayLogger implements LoggerInterface
{
    /** @var array */
    private $logs = [];
    
    public function emergency($message, array $context = [])
    {
        $this->log('message', $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = [])
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

    public function messages($level)
    {
        $list = $this->retrieve($level);
        $return = [];
        foreach ($list as $element) {
            $return[] = $element['message'];
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
}
