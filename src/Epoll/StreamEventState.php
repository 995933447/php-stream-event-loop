<?php
namespace Bobby\StreamEventLoop\Epoll;

use Bobby\StreamEventLoop\Utils\MagicGetterTrait;
use Event;

class StreamEventState
{
    use MagicGetterTrait;

    protected $stream;

    protected $event;

    protected $isReadable;

    protected $isWritable;

    protected $listener;

    public function __construct($stream, Event $event, bool $isReadable, bool $isWritable, callable $callback)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException(__METHOD__ . "() passed first agrument except resource, " . gettype($stream) . " given." );
        }
        $this->stream = $stream;
        $this->event = $event;
        $this->isReadable = $isReadable;
        $this->isWritable = $isWritable;
        $this->listener = $callback;
    }

    public function setIsReadable(bool $isReadable)
    {
        $this->isReadable = $isReadable;
    }

    public function setIsWritable(bool $isWritable)
    {
        $this->isWritable = $isWritable;
    }

    public static function isReadEventType(int $eventType)
    {
        return ($eventType & Loop::READ_EVENT) === Loop::READ_EVENT;
    } 

    public static function isWriteEventType(int $eventType)
    {
        return ($eventType & Loop::WRITE_EVENT) === Loop::WRITE_EVENT;
    }
}