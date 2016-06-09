<?php namespace EngineWorks\DBAL\Tests\Sample;

use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    /** @var array */
    private $logs = [];
    
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
