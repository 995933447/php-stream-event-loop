<?php
$resources = [];
$events = [];

$base = new EventBase();

$server = stream_socket_server('tcp://0.0.0.0:8080');

// all pass
//$event = new Event($base, $server, Event::READ | Event::PERSIST, function ($server) {
//    $accept = stream_socket_accept($server);
//    global $resources;
//    $resources[] = $accept;
//
//    global $events;
//    global $base;
//    $events[] = $eventBufferEvent = new EventBufferEvent($base, $accept);
//    $eventBufferEvent->setCallbacks(function ($eventBufferEvent) use ($accept) {
//        echo "data:" . $eventBufferEvent->getInput()->pullup(-1) . PHP_EOL;
//    }, function ($eventBufferEvent) use ($accept) {
//        $content = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi";
//        $eventBufferEvent->write($content);
//        var_dump($accept);
//        echo "Writed.\n";
//        sleep(3);
//    }, function ($eventBufferEvent, $events) {
//        if ($events & EventBufferEvent::CONNECTED) {
//            echo "connected\n";
//        } elseif ($events & EventBufferEvent::TIMEOUT) {
//            echo "timeout\n";
//        } elseif ($events & (EventBufferEvent::ERROR)) {
//            echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
//        } elseif ($events & (EventBufferEvent::EOF)) {
//            echo "eof\n";
//        }
//    });
//
//    $eventBufferEvent->enable(Event::READ | Event::WRITE);
//});

//$event = new Event($base, $server, Event::READ | Event::PERSIST, function ($server) {
//    $accept = stream_socket_accept($server);
//    global $resources;
//    $resources[] = $accept;
//
//    global $events;
//    global $base;
//    $events[] = $eventBufferEvent = new EventBufferEvent($base, $accept);
//    $eventBufferEvent->setCallbacks(function ($eventBufferEvent) use ($accept) {
//        echo "data:" . $eventBufferEvent->getInput()->pullup(-1) . PHP_EOL;
//        $eventBufferEvent->enable(Event::WRITE);
//    }, function ($eventBufferEvent) use ($accept) {
//        $content = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi";
//        $eventBufferEvent->write($content);
//        var_dump($accept);
//        echo "Writed.\n";
//        sleep(3);
//    }, function ($eventBufferEvent, $events) {
//        if ($events & EventBufferEvent::CONNECTED) {
//            echo "connected\n";
//        } elseif ($events & EventBufferEvent::TIMEOUT) {
//            echo "timeout\n";
//        } elseif ($events & (EventBufferEvent::ERROR)) {
//            echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
//        } elseif ($events & (EventBufferEvent::EOF)) {
//            echo "eof\n";
//        }
//    });
//
//    $eventBufferEvent->enable(Event::READ);
//});

//$event = new Event($base, $server, Event::READ | Event::PERSIST, function ($server) {
//    $accept = stream_socket_accept($server);
//    global $resources;
//    $resources[] = $accept;
//
//    global $events;
//    global $base;
//    $events[] = $eventBufferEvent = new EventBufferEvent($base, $accept);
//    $eventBufferEvent->setCallbacks(function ($eventBufferEvent) use ($accept) {
//        echo "data:" . $eventBufferEvent->getInput()->pullup(-1) . PHP_EOL;
//        $eventBufferEvent->setCallbacks(null, function ($eventBufferEvent) {
//            $content = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi";
//            $eventBufferEvent->write($content);
//        }, function ($eventBufferEvent, $events) {
//            if ($events & EventBufferEvent::CONNECTED) {
//                echo "connected\n";
//            } elseif ($events & EventBufferEvent::TIMEOUT) {
//                echo "timeout\n";
//            } elseif ($events & (EventBufferEvent::ERROR)) {
//                echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
//            } elseif ($events & (EventBufferEvent::EOF)) {
//                echo "eof\n";
//            }
//        });
//
//        $eventBufferEvent->enable(Event::READ | Event::WRITE);
//    }, null, function ($eventBufferEvent, $events) {
//        if ($events & EventBufferEvent::CONNECTED) {
//            echo "connected\n";
//        } elseif ($events & EventBufferEvent::TIMEOUT) {
//            echo "timeout\n";
//        } elseif ($events & (EventBufferEvent::ERROR)) {
//            echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
//        } elseif ($events & (EventBufferEvent::EOF)) {
//            echo "eof\n";
//        }
//    });
//
//    $eventBufferEvent->enable(Event::READ | Event::WRITE);
//});


$event = new Event($base, $server, Event::READ | Event::PERSIST, function ($server) {
    $accept = stream_socket_accept($server);
    global $resources;
    $resources[] = $accept;

    global $events;
    global $base;
    $events[] = $eventBufferEvent = new EventBufferEvent($base, $accept);
    $eventBufferEvent->setCallbacks(function ($eventBufferEvent) use ($accept) {
        echo "data:" . $eventBufferEvent->getInput()->pullup(-1) . PHP_EOL;
        $content = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi";
        $eventBufferEvent->write($content);
    }, null, function ($eventBufferEvent, $events) {
        if ($events & EventBufferEvent::CONNECTED) {
            echo "connected\n";
        } elseif ($events & EventBufferEvent::TIMEOUT) {
            echo "timeout\n";
        } elseif ($events & (EventBufferEvent::ERROR)) {
            echo 'Socket error:' . \EventUtil::getLastSocketError() . PHP_EOL;
        } elseif ($events & (EventBufferEvent::EOF)) {
            echo "eof\n";
        }
    });

    $eventBufferEvent->enable(Event::READ);
});


$event->add();

$base->loop();