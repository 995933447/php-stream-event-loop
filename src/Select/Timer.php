<?php
namespace Bobby\StreamEventLoop\Select;

use Bobby\StreamEventLoop\Utils\MagicGetterTrait;

class Timer
{
    use MagicGetterTrait;

    const TICK_TYPE = 0;
    const AFTER_TYPE = 1;

    protected $timerId;

    protected $interval;

    protected $timeout;

    protected $listener;

    protected $type;

    public function __construct(int $timerId)
    {
        $this->timerId = $timerId;
    }

    public function setInterval(float $interval)
    {
        $this->interval = $interval;
        $this->updateTimeout();
    }

    protected function updateTimeout()
    {
        $this->timeout = microtime(true) + $this->interval;
    }

    public function setListener(callable $callback)
    {
        $this->listener = $callback;
    }

    public function setType(int $type)
    {
        $this->type = $type;
    }

    public function execute(Loop $loop)
    {
        if ($this->timeout <= microtime(true)) {
            call_user_func_array($this->listener, [$this->timerId, $loop]);
            $this->updateTimeout();
            return true;
        }
        return false;
    }
}