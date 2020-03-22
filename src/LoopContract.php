<?php
namespace Bobby\StreamEventLoop;

abstract class LoopContract
{
    const READ_EVENT = 1;
    const WRITE_EVENT = 2;

    protected $executorWhenWaitingQueue;

    protected $onCycleStart;

    protected $onCycleEnd;

    /** 一轮事件循环开始前触发回调
     * @param $callback
     */
    public function onCycleStart($callback)
    {
        if (!is_null($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException();
        }
        $this->onCycleStart = $callback;
    }

    /** 一轮事件事件循环结束后触发
     * @param $callback
     */
    public function onCycleEnd($callback)
    {
        if (!is_null($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException();
        }
        $this->onCycleEnd = $callback;
    }

    protected function emitOnCycleStart()
    {
        if (!is_null($this->onCycleStart)) {
            call_user_func_array($this->onCycleStart, [$this]);
        }
    }

    protected function emitOnCycleEnd()
    {
        if (!is_null($this->onCycleEnd)) {
            call_user_func_array($this->onCycleEnd, [$this]);
        }
    }

    /**当没有事件准备好时,利用闲置时间执行额外任务
     * @param callable $callback
     * @return mixed
     */
    public function addWhenWaiting(callable $callback)
    {
        $this->getExecutorWhenWaitingQueue()->addExecutor($callback);
    }

    protected function getExecutorWhenWaitingQueue(): ExecutorWhenWaitingQueue
    {
        if (is_null($this->executorWhenWaitingQueue)) {
            $this->executorWhenWaitingQueue = new ExecutorWhenWaitingQueue($this);
        }
        return $this->executorWhenWaitingQueue;
    }

    /** 检测是否存在利用闲置时间额外执行的任务
     * @return bool
     */
    public function isEmptyWhenWaiting(): bool
    {
        return $this->getExecutorWhenWaitingQueue()->isEmpty();
    }

    /**添加要添加的事件循环的资源流
     * @param int $eventType
     * @param $stream
     * @param callable $callback
     * @return mixed
     */
    abstract public function addLoopStream(int $eventType, $stream, callable $callback);

    /**从事件循环移除资要监听的源流
     * @param int $eventType
     * @param $stream
     * @return mixed
     */
    abstract public function removeLoopStream(int $eventType, $stream);

    /**添加信号处理器
     * @param int $signalNo
     * @param callable $callback
     * @return mixed
     */
    abstract public function installSignal(int $signalNo, $callback);

    /**移除信号处理器
     * @param int $signalNo
     * @return mixed
     */
    abstract public function removeSignal(int $signalNo);

    /**添加持续触发定时器
     * @param float $interval
     * @param callable $callback
     * @return int
     */
    abstract public function addTick(float $interval, callable $callback): int;

    /**添加一次性触发定时器
     * @param float $interval
     * @param callable $callback
     * @return int
     */
    abstract public function addAfter(float $interval, callable $callback): int;

    /**移除定时器
     * @param int $timerId
     * @return mixed
     */
    abstract public function removeTimer(int $timerId);

    /**开始时间循环
     * @return mixed
     */
    abstract public function poll();

    /**中断时间循环
     * @return mixed
     */
    abstract public function stop();

    /**检查是否存在准备可读事件流
     * @return bool
     */
    abstract public function isEmptyReadyReadStream(): bool;

    /**检测是否存在准备可写事件流
     * @return bool
     */
    abstract public function isEmptyReadyWriteStream(): bool;

    /**检测是否存在持续定时器
     * @return bool
     */
    abstract public function isEmptyTimer(): bool;

    /**检测是否存在已安装的信号处理器
     * @return bool
     */
    abstract public function isEmptySignals(): bool;
}