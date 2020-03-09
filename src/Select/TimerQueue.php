<?php
namespace Bobby\StreamEventLoop\Select;

use SplPriorityQueue;

class TimerQueue extends SplPriorityQueue
{
    protected $minInterval;

    protected $poppedTimers = [];

    protected $maxTimerId = 0;

    protected $loop;

    public function __construct(Loop $loop)
    {
        $this->loop = $loop;
    }

    public function getMinInterval(): ?float
    {
        return $this->minInterval;
    }

    public function addTick(float $seconds, callable $callback): int
    {
        return $this->addScheduleTimer($seconds, $callback, Timer::TICK_TYPE);
    }

    public function addAfter(float $seconds, callable $callback): int
    {
        return $this->addScheduleTimer($seconds, $callback, Timer::AFTER_TYPE);
    }

    public function addScheduleTimer(float $seconds, callable $callback, $type): int
    {
        if ($seconds <= 0) {
            throw new \InvalidArgumentException(__METHOD__ . '() passed first argument except be greater thon 0.');
        }

        $timer = new Timer($this->maxTimerId++);
        $timer->setInterval($seconds);
        $timer->setListener($callback);
        $timer->setType($type);
        $this->insertTimer($timer);

        return $timer->timerId;
    }

    protected function insertTimer(Timer $timer)
    {
        $this->insert($timer, -1 * $timer->interval);
        $this->resetMinInterVal();
    }

    protected function resetMinInterVal()
    {
        if ($this->isEmpty()) {
            if (!is_null($this->minInterval)) {
                $this->minInterval = null;
            }
            return;
        }

        $this->setExtractFlags(SplPriorityQueue::EXTR_PRIORITY);
        $this->minInterval = -1 * $this->top();
    }

    public function pop(bool $needRecycle = true): ?Timer
    {
        if ($this->isEmpty()) {
            return null;
        }

        $this->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $timer = $this->top();
        $this->extract();

        if ($needRecycle) {
            $this->poppedTimers[] = $timer;
        }

        $this->resetMinInterVal();

        return $timer;
    }

    public function recycle()
    {
        while (!empty($this->poppedTimers)) {
            $this->insertTimer(array_pop($this->poppedTimers));
        }
    }

    public function removeTimer(int $timerId)
    {
        foreach ($this->poppedTimers as $index => $poppedTimer) {
            if ($poppedTimer->timerId == $timerId) {
                unset($this->poppedTimers[$index]);
                return;
            }
        }

        $timersNum = $this->count();
        $poppedTimers = [];
        while ($timersNum--) {
            $timer = $this->pop(false);
            if ($timer->timerId == $timerId) {
                $this->resetMinInterVal();
                break;
            }
            $poppedTimers[] = $timer;
        }

        foreach ($poppedTimers as $timer) {
            $this->insertTimer($timer);
        }
    }

    public function schedule()
    {
        while (!$this->isEmpty()) {
            if (($timer = $this->pop())->execute($this->loop) && $timer->type == Timer::AFTER_TYPE) {
                $this->removeTimer($timer->timerId);
            }
        }
        $this->recycle();
    }
}