<?php
$seeds = [
    "timer.php",
    "libevent.php"
];

$events = [];
$base = new EventBase();
$resources = [];

foreach ($seeds as $file) {
    if (!$resource = fopen($file, 'r')) {
        exit("error");
    };


    $resources[] = $resource;

    $events[] = $eventBufferEvent = new EventBufferEvent($base, $resource);
    $eventBufferEvent->setCallbacks(function ($eventBufferEvent) use ($file) {
        echo "$file data:" . $eventBufferEvent->getInput()->pullup(-1) . PHP_EOL;
        sleep(2);
    }, NULL, function ($eventBufferEvent, $events) use ($file) {
        if ($events & \EventBufferEvent::CONNECTED) {
            echo "connected\n";
        } elseif ($events & \EventBufferEvent::TIMEOUT) {
            echo "timeout\n";
        } elseif ($events & (\EventBufferEvent::ERROR)) {
            echo "$file error\n";
            echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
        } elseif ($events & (\EventBufferEvent::EOF)) {
            echo "$file eof\n";
        }
    });

    $eventBufferEvent->enable(Event::READ);
}

$base->loop();