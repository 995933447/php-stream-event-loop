<?php
namespace Bobby\StreamEventLoop\Select;

use Bobby\StreamEventLoop\LoopContract;

class Loop extends LoopContract
{
    protected $timerQueue;

    protected $streamEventContainer;

    protected $signalArtisan;

    protected $isRunning = false;

    public function poll()
    {
        $this->isRunning = true;
        $this->getSignalArtisan()->makeDelayed();

        while ($this->isRunning) {
            list($readyReadStreams, $readyWriteStreams) = $this->getStreamEventContainer()->toArray();

            if (
                $this->isEmptyReadyReadStream()
                && $this->isEmptyReadyWriteStream()
                && $this->isEmptyTimer()
                && $this->isEmptySignals()
                && $this->isEmptyWhenWaiting()
            ) {
                $this->stop();
                continue;
            }

            $this->getSignalArtisan()->detonate();

            $minTimerTimeout = null;
            if (!$this->getTimerQueue()->isEmpty()) {
                $minTimerTimeout = $this->getTimerQueue()->getMinTimeout();
            }

            $selectTimeout = null;
            if ($this->getExecutorWhenWaitingQueue()->isEmpty()) {
                if (!is_null($minTimerTimeout)) {
                    if ($minTimerTimeout > microtime(true)) {
                        $selectTimeout = ($minTimerTimeout - microtime(true)) * 1000000;
                    } else {
                        $selectTimeout = 0;
                    }
                }
            } else {
                $selectTimeout = 0;
            }

            $this->emitOnCycleStart();

            $hasReadyEvents = false;
            if ($readyReadStreams || $readyWriteStreams) {
                $readyCatchExceptionStreams = [];
                 if (@stream_select($readyReadStreams, $readyWriteStreams, $readyCatchExceptionStreams, is_null($selectTimeout) ? null: 0, $selectTimeout)) {
                     foreach ($readyWriteStreams as $readyWriteStream) {
                         call_user_func_array($this->getStreamEventContainer()->getWritableEventListener($readyWriteStream), [$readyWriteStream, $this, self::WRITE_EVENT]);
                     }

                     foreach ($readyReadStreams as $readyReadStream) {
                         call_user_func_array($this->getStreamEventContainer()->getReadableEventListener($readyReadStream), [$readyReadStream, $this, self::READ_EVENT]);
                     }

                     $hasReadyEvents = true;
                 }
            } else if (!$this->getTimerQueue()->isEmpty()) {
                usleep($selectTimeout);
            }

            if (!is_null($minTimerTimeout) && microtime(true) >= $minTimerTimeout) {
                $hasReadyEvents = true;
                $this->getTimerQueue()->schedule();
            }

            if (!$hasReadyEvents) {
                $this->getExecutorWhenWaitingQueue()->schedule();
            }

            $this->emitOnCycleEnd();
        }
    }

    public function stop()
    {
        $this->isRunning = false;
    }

    public function addLoopStream(int $eventType, $stream, callable $callback)
    {
        $this->getStreamEventContainer()->add($eventType, $stream, $callback);
    }

    public function removeLoopStream(int $eventType, $stream)
    {
        $this->getStreamEventContainer()->remove($eventType, $stream);
    }

    protected function getStreamEventContainer(): StreamEventContainer
    {
        if (is_null($this->streamEventContainer)) {
            $this->streamEventContainer = new StreamEventContainer();
        }

        return $this->streamEventContainer;
    }

    public function installSignal(int $signalNo, $callback)
    {
        if ($this->isRunning) {
            $this->getSignalArtisan()->make($signalNo, $callback);
        } else {
            $this->getSignalArtisan()->delayMake($signalNo, $callback);
        }
    }

    public function removeSignal(int $signalNo)
    {
        $this->installSignal($signalNo, null);
    }

    public function getSignalArtisan()
    {
        if (is_null($this->signalArtisan)) {
            $this->signalArtisan = new SignalArtisan();
        }

        return $this->signalArtisan;
    }

    public function addTick(float $interval, callable $callback): int
    {
        return $this->getTimerQueue()->addTick($interval, $callback);
    }

    public function addAfter(float $interval, callable $callback): int
    {
        return $this->getTimerQueue()->addAfter($interval, $callback);
    }

    public function removeTimer(int $timerId)
    {
        $this->getTimerQueue()->removeTimer($timerId);
    }

    protected function getTimerQueue(): TimerQueue
    {
        if (is_null($this->timerQueue)) {
            $this->timerQueue = new TimerQueue($this);
        }

        return $this->timerQueue;
    }

    /**
     * @inheritDoc
     */
    public function isEmptyReadyReadStream(): bool
    {
        return empty($readyReadStreams);
    }

    /**
     * @inheritDoc
     */
    public function isEmptyReadyWriteStream(): bool
    {
        return empty($readyWriteStreams);
    }

    /**
     * @inheritDoc
     */
    public function isEmptyTimer(): bool
    {
        return $this->getTimerQueue()->isEmpty();
    }


    /**
     * @inheritDoc
     */
    public function isEmptySignals(): bool
    {
        return $this->getSignalArtisan()->isEmpty();
    }
}