<?php
namespace Bobby\StreamEventLoop\Select;

use Bobby\StreamEventLoop\LoopContract;

class StreamEventContainer
{
    protected $readableEventListeners = [];
    protected $readyReadStreams = [];

    protected $readyWriteStreams = [];
    protected $writableEventListeners = [];

    public function getReadableEventListener($stream): callable
    {
        return $this->readableEventListeners[(int)$stream];
    }

    public function getWritableEventListener($stream): callable
    {
        return $this->writableEventListeners[(int)$stream];
    }

    public function add(int $eventType, $stream, callable $callback)
    {
        list($listenerSavers, $readyStreamSavers) = $this->parseSavers($eventType);

        foreach ($listenerSavers as &$listenerSaver) {
            $listenerSaver[(int)$stream] = $callback;
        }

        foreach ($readyStreamSavers as &$readyStreamSaver) {
            $readyStreamSaver[] = $stream;
        }
    }

    public function remove(int $eventType, $stream)
    {
        list($listenerSavers, $readyStreamSavers) = $this->parseSavers($eventType);

        foreach ($readyStreamSavers as &$readyStreamSaver) {
            if (($streamIndex = array_search($stream, $readyStreamSaver)) !== false) {
                unset($readyStreamSaver[$streamIndex]);
            }
        }

        foreach ($listenerSavers as &$listenerSaver) {
            if (isset($listenerSaver[$streamId = (int)$stream])) unset($listenerSaver[$streamId]);
        }
    }

    protected function parseSavers(int $eventType): array
    {
        $listenerSavers = [];
        $readyStreamSavers = [];

        if (($eventType & LoopContract::READ_EVENT) === LoopContract::READ_EVENT) {
            $listenerSavers[] = &$this->readableEventListeners;
            $readyStreamSavers[] = &$this->readyReadStreams;
        }

        if (($eventType & LoopContract::WRITE_EVENT) === LoopContract::WRITE_EVENT) {
            $listenerSavers[] = &$this->writableEventListeners;
            $readyStreamSavers[] = &$this->readyWriteStreams;
        }

        return [$listenerSavers, $readyStreamSavers];
    }

    public function toArray(): array
    {
        return [$this->readyReadStreams, $this->readyWriteStreams];
    }
}