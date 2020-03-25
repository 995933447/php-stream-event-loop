<?php
namespace Bobby\StreamEventLoop;

use Bobby\StreamEventLoop\Epoll\Loop as EpollLoop;
use Bobby\StreamEventLoop\Select\Loop as SelectLoop;
use Event;
use EventBase;

class LoopFactory
{
    const EPOLL_LOOP = 0;

    const SELECT_LOOP = 1;

    /**产出一个Loop实例
     * @param int|null $loopType
     * @return LoopContract
     */
    public static function make(int $loopType = null): LoopContract
    {
        if (is_null($loopType)) {
            if (class_exists(Event::class, false) && class_exists(EventBase::class, false)) {
                $loopType = static::EPOLL_LOOP;
            } else {
                $loopType = static::SELECT_LOOP;
            }
        }

        return static::build($loopType);
    }

    /**创建一个Loop示例
     * @param int $loopType
     * @return LoopContract
     */
    public static function build(int $loopType): LoopContract
    {
        switch ($loopType) {
            case self::EPOLL_LOOP:
                return new EpollLoop();
            case self::SELECT_LOOP:
                return new SelectLoop();
        }
    }
}
