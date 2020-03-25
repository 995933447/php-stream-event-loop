<?php
$host = '0.0.0.0';
$port = 9501;
$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($server, $host, $port);
socket_listen($server);
echo PHP_EOL.PHP_EOL."Http Server ON : http://{$host}:{$port}".PHP_EOL;

socket_set_nonblock($server);

$eventBase = new EventBase(new EventConfig);
$event = new Event($eventBase, $server, Event::READ | Event::PERSIST, function($socket) {
    if(($connetSocket = socket_accept($socket)) !== false) {
        echo '有新的客户端：' . intval($socket) . PHP_EOL;
        $msg = '"HTTP/1.0 200 OK\r\nContent-Length: 2\r\n\r\nHi' . PHP_EOL;
        socket_write($connetSocket, $msg, strlen($msg));
        socket_close($connetSocket);
    }
});

$connetSocket = null;
$event2 = null;
$event->set($eventBase, $server, Event::READ  | Event::WRITE | Event::PERSIST, function ($socket) use ($eventBase) {
    global $connetSocket;
    if(($connetSocket = socket_accept($socket)) !== false) {
        echo 'v2:有新的客户端：' . intval($socket) . PHP_EOL;
        $msg = '"HTTP/1.0 200 OK\r\nContent-Length: 2\r\n\r\nHi' . PHP_EOL;
        
        global $event2;
        $event2 = new Event($eventBase, $connetSocket, Event::WRITE | Event::PERSIST, function($socket) {
            $msg = '"HTTP/1.0 200 OK\r\nContent-Length: 2\r\n\r\nHi' . PHP_EOL;
            socket_write($socket, $msg, strlen($msg));
            socket_close($socket);
        });
        $event2->add();
    }
});

// $event->set($eventBase, $server, Event::WRITE | Event::PERSIST, function ($socket) {
//     if(($connetSocket = socket_accept($socket)) !== false) {
//         echo 'v3:有新的客户端：' . intval($socket) . PHP_EOL;
//         $msg = '"HTTP/1.0 200 OK\r\nContent-Length: 2\r\n\r\nHi' . PHP_EOL;
//         socket_write($connetSocket, $msg, strlen($msg));
//         socket_close($connetSocket);
//     }
// });
$event->add();
$eventBase->loop();