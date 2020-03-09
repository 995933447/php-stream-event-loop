<?php
namespace Bobby\StreamEventLoop\Select;

class SignalArtisan
{
    protected $installedSignals = [];
    protected $readyBuildSignals = [];

    protected $isDetonated = false;

    public function delayMake(int $signalNo, $callback)
    {
        $this->readyBuildSignals[$signalNo] = $callback;
        $this->freshInstalled($signalNo, $callback);
    }

    public function make(int $signalNo, $callback)
    {
        $this->build($signalNo, $callback);
        $this->freshInstalled($signalNo, $callback);
    }

    protected function freshInstalled(int $signalNo, $callback)
    {
        if (is_null($callback) && ($signalIndex = array_search($signalNo, $this->installedSignals)) !== false) {
            unset($this->installedSignals[$signalIndex]);
        } else if (!in_array($signalNo, $this->installedSignals)) {
            $this->installedSignals[] = $signalNo;
        }
    }

    protected function build($signalNo, $callback)
    {
        pcntl_signal($signalNo, $callback);
    }

    public function makeDelayed()
    {
        foreach ($this->readyBuildSignals as $signalNo => $signalHandle)
        {
            $this->build($signalNo, $signalHandle);
            unset($this->readyBuildSignals[$signalNo]);
        }
    }

    public function isEmpty()
    {
        return empty($this->installedSignals);
    }

    public function detonate()
    {
        if (!$this->isEmpty() && !$this->isDetonated) {
            if (function_exists('pcntl_async_signals')) {
                pcntl_async_signals(true);
                $this->isDetonated = true;
            } else {
                pcntl_signal_dispatch();
            }
        }
    }
}