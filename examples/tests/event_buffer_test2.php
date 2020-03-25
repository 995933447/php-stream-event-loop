<?php

$seeds = [
    'www.baidu.com',
    'www.qq.com',
    'www.taobao.com'
];

$events = [];
$base = new EventBase();
$resources = [];
foreach ($seeds as $host) {
    if (!$resource = fsockopen($host, 80, $errno, $errstr)) {
        exit($errstr);
    };

    stream_set_blocking($resource, false);
    $resources[] = $resource;

    $events[] = $eventBufferEvent = new EventBufferEvent($base, $resource, EventBufferEvent::OPT_CLOSE_ON_FREE);
    $eventBufferEvent->setCallbacks(function ($eventBufferEvent) use ($host) {
//        echo "Data:" . $eventBufferEvent->read(30) . PHP_EOL;
        echo "$host data:" . $eventBufferEvent->getInput()->pullup(-1) . PHP_EOL;
        sleep(2);
    }, function ($eventBufferEvent) use ($host) {
        $content = "GET / HTTP/1.1\r\n";
        $content .= "Host: $host\r\n";
        $content .= "Connection: close\r\n\r\n";

        $eventBufferEvent->write($content);
    }, function ($eventBufferEvent, $events) use ($host) {
        if ($events & \EventBufferEvent::CONNECTED) {
            echo "connected\n";
        } elseif ($events & \EventBufferEvent::TIMEOUT) {
            echo "timeout\n";
        } elseif ($events & (\EventBufferEvent::ERROR)) {
            echo "$host error\n";
            echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
        } elseif ($events & (\EventBufferEvent::EOF)) {
            echo "$host eof\n";
        }
    });

    $eventBufferEvent->enable(Event::READ|Event::WRITE);
}

$base->loop();