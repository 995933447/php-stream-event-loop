<?php
require __DIR__ . "/../vendor/autoload.php";

use Bobby\StreamEventLoop\LoopContract as Loop;
use Bobby\StreamEventLoop\LoopFactory;
$loop = LoopFactory::make();

if ($loop instanceof \Bobby\StreamEventLoop\Select\Loop) {
    echo "Select type event loop\n";
} else {
    echo "lib event type event loop\n";
}

$loop->addTick(0.5, function (int $timerId, Loop $loop) {
    echo "\nTick id: $timerId\n";
});

$loop->addTick(2.6, function () {
    echo "\n2.6\n";
});

$loop->addAfter(10, function (int $timeId, Loop $loop) {
    echo "\nremove $timeId\n";
    $loop->removeTimer($timeId);
});

$loop->addTick(1, function (int $timerId, Loop $loop) {
    echo "\nTick id: $timerId\n";
});

$loop->poll();
