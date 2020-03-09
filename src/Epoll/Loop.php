<?php
namespace Bobby\StreamEventLoop\Epoll;

use Bobby\StreamEventLoop\LoopContract;
use EventConfig;
use EventBase;
use Event;

class Loop extends LoopContract
{
    protected $eventBase;

    protected $streamEvents = [];

    protected $signals = [];

    protected $timers = [];

    protected $maxTimerId = 0;

    protected $isRunning = false;

    protected $hasEventsReady = false;

    public function poll()
    {
        $this->isRunning = true;

        $flags = EventBase::LOOP_ONCE;

        while ($this->isRunning) {

            $this->emitOnCycleStart();

            $this->hasEventsReady = false;

            if (!$this->isEmptyEvents()) {
                if (!$this->getExecutorWhenWaitingQueue()->isEmpty()) {
                    $flags |= EventBase::LOOP_NONBLOCK;
                }

                $this->getEventBase()->loop($flags);
            }

            if (!$this->hasEventsReady) {
                $this->getExecutorWhenWaitingQueue()->schedule();
            }

            $this->emitOnCycleEnd();

            if ($this->getExecutorWhenWaitingQueue()->isEmpty() && $this->isEmptyEvents()) {
                $this->stop();
            }
        }
    }

    protected function isEmptyEvents(): bool
    {
        return empty($this->streamEvents) && empty($this->signals) && empty($this->timers);
    }

    public function stop()
    {
        $this->getEventBase()->stop();
        $this->isRunning = false;
    }

    protected function getEventBase(): EventBase
    {
        if (is_null($this->eventBase)) {
            $eventConfig = new EventConfig();
            $eventConfig->requireFeatures(EventConfig::FEATURE_FDS);
            $this->eventBase = new EventBase($eventConfig);
        }

        return $this->eventBase;
    }

    protected function eventTypeToLibEventType(int $eventType): int
    {
        $libEventType =  Event::PERSIST;
        
        if (StreamEventState::isReadEventType($eventType)) {
            $libEventType |= Event::READ;
        }
        
        if (StreamEventState::isWriteEventType($eventType)) {
            $libEventType |= Event::WRITE;
        }

        if ($libEventType === Event::PERSIST) {
            throw new \InvalidArgumentException('event type must be read type:' . self::READ_EVENT . ' or write type:' . self::WRITE_EVENT . '.');
        }

        return $libEventType;
    }

    protected function libEventTypeToEventType(int $libEventType): int
    {
        $eventType = 0;
        
        if (($libEventType & Event::READ) === Event::READ) {
            $eventType |= self::READ_EVENT;
        }
        
        if (($libEventType & Event::WRITE) === Event::WRITE) {
            $eventType |= self::WRITE_EVENT;
        }

        if ($eventType === 0) {
            throw new \InvalidArgumentException('libevent type is invalid.');
        }

        return $eventType;
    }

    public function addLoopStream(int $eventType, $stream, callable $callback)
    {
        $event = new Event($this->getEventBase(), $stream, $this->eventTypeToLibEventType($eventType), $this->normalizeStreamEventCallback($callback));
        $event->add();

        $this->streamEvents[(int)$stream] = new StreamEventState(
                $stream,
                $event,
                ($eventType & self::READ_EVENT) === self::READ_EVENT,
                ($eventType & self::WRITE_EVENT) === self::WRITE_EVENT,
                $callback
            );
    }

    protected function normalizeStreamEventCallback(callable $callback)
    {
        return function ($stream, $libEventType) use ($callback) {
            $this->hasEventsReady = true;
            call_user_func_array($callback, [$stream, $this, $this->libEventTypeToEventType($libEventType)]);
        };
    }

    public function removeLoopStream(int $eventType, $stream)
    {
        if (!isset($this->streamEvents[$streamKey = (int)$stream])) {
            return;
        }

        $eventState = $this->streamEvents[$streamKey];

        $originalIsWriteEvenType = $eventState->isWritable;
        $originalIsReadEventType = $eventState->isReadable;    

        if (StreamEventState::isWriteEventType($eventType)) {
            $eventState->setIsWritable(false);
        } 

        if (StreamEventState::isReadEventType($eventType)) {
            $eventState->setIsReadable(false);
        } 

        if (!$eventState->isWritable && !$eventState->isReadable) {
            $eventState->event->free();
            unset($this->streamEvents[$streamKey]);
            return;
        }

        if (!$eventState->isWritable && $originalIsWriteEvenType) {
            $eventState->event->set($this->getEventBase(), Event::READ | Event::PERSIST, $eventState->listener);
        }

        if (!$eventState->isReadable && $originalIsReadEventType) {
            $eventState->event->set($this->getEventBase(), Event::WRITE | Event::PERSIST, $eventState->listener);
        }
    }

    public function installSignal(int $signalNo, $callback)
    {
        $this->signals[$signalNo] = Event::signal($this->getEventBase(), $signalNo, $callback);
        $this->signals[$signalNo]->add();
    }

    public function removeSignal(int $signalNo)
    {
        if (isset($this->signals[$signalNo])) {
            $this->signals[$signalNo]->free();
            unset($this->signals[$signalNo]);
        }
    }

    public function addTick(float $interval, callable $callback): int
    {
        return $this->addTimer($interval, $callback, true);
    }

    public function addAfter(float $interval, callable $callback): int
    {
        return $this->addTimer($interval, $callback, false);
    }

    protected function addTimer(float $interval, callable $callback, bool $isTick): int
    {
        $timerId = $this->maxTimerId++;

        $this->timers[$timerId] = $isTick?
            new Event($this->getEventBase(), -1, Event::TIMEOUT | Event::PERSIST, $this->normalizeTimerCallBack($callback, $timerId)):
            Event::timer($this->getEventBase(), $this->normalizeTimerCallBack($callback, $timerId));

        $this->timers[$timerId]->add($interval);

        return $timerId;
    }

    protected function normalizeTimerCallBack(callable $callback, int $timerId)
    {
        return function () use ($callback, $timerId) {
            $this->hasEventsReady = true;
            call_user_func_array($callback, [$timerId, $this]);
        };
    }

    public function removeTimer(int $timerId)
    {
        if (isset($this->timers[$timerId])) {
            $this->timers[$timerId]->free();
            unset($this->timers[$timerId]);
        }
    }
}