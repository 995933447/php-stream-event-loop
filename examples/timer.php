<?php
require __DIR__ . "/../vendor/autoload.php";

use Bobby\StreamEventLoop\LoopContract as Loop;
use Bobby\StreamEventLoop\LoopFactory;

$loop = LoopFactory::make(LoopFactory::SELECT_LOOP);

$loop->addTick(0.5, function (int $timerId, Loop $loop) {
    echo "Tick id: $timerId\n";
});

$loop->addAfter(10, function (int $timeId, Loop $loop) {
    echo "remove $timeId\n";
    $loop->removeTimer($timeId);
});

$loop->addTick(1, function (int $timerId, Loop $loop) {
    echo "Tick id: $timerId\n";
});

$loop->poll();