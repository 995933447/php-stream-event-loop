<?php
namespace Bobby\StreamEventLoop;

class ExecutorWhenWaitingQueue extends \SplQueue
{
    protected $loop;

    public function __construct(LoopContract $loop)
    {
        $this->loop = $loop;
    }

    /**添加等待事件准备好时利用闲置时间执行的任务，当没有事件准备好时可以执行队列任务以利用闲置时间进行其他程序
     * @param callable $callback
     */
    public function addExecutor(callable $callback)
    {
        $this->enqueue($callback);
    }

    /**调度队列任务.从队列首获取一个任务执行完成后并消耗掉,下次调用则获取下个任务并执行,队列每次调用任务数量都会减少.队列为空则不进行任何动作
     * @return bool
     */
    public function schedule(): bool
    {
        if (!$this->isEmpty()) {
            call_user_func_array($this->dequeue(), [$this->loop]);
            return true;
        }
        return false;
    }
}