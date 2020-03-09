<?php
require __DIR__ . "/../vendor/autoload.php";

use Bobby\StreamEventLoop\LoopContract as Loop;
use Bobby\StreamEventLoop\LoopFactory;

$loop = LoopFactory::make(LoopFactory::SELECT_LOOP);
//$loop = LoopFactory::make();

if ($loop instanceof \Bobby\StreamEventLoop\Select\Loop) {
    echo "Select event loop\n";
} else if ($loop instanceof \Bobby\StreamEventLoop\Epoll\Loop) {
    echo "Epoll event loop\n";
}

$server = stream_socket_server('tcp://0.0.0.0:8080');
stream_set_blocking($server, false);

$loop->addLoopStream(Loop::READ_EVENT, $server, function ($server, Loop $loop, int $eventTypes) {
    $conn = stream_socket_accept($server);
    var_dump(var_dump(stream_socket_get_name($conn, false)));
    stream_set_blocking($conn, false);

    $data = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi";
    $loop->addLoopStream(Loop::WRITE_EVENT, $conn, function ($conn, Loop $loop, int $eventTypes) use ($data) {
        echo "start read.\n";
        echo "Server receive data " . fread($conn, 1024) . "\n";
        $written = fwrite($conn, $data);
        echo "writed\n";
        if ($written === strlen($data)) {
            fclose($conn);
            $loop->removeLoopStream(Loop::WRITE_EVENT, $conn);
        } else {
            $data = substr($data, $written);
            echo "$data didn't send.\n";
        }
    });
});

$tickId = $loop->addTick(0.5, function (int $timerId, Loop $loop) {
     echo "Tick id: $timerId\n";
});

$loop->addAfter(10, function (int $timeId, Loop $loop) use ($tickId) {
    echo "remove $timeId\n";
    $loop->removeTimer($tickId);
});

$loop->addTick(1, function (int $timerId, Loop $loop) {
     echo "Tick id: $timerId\n";
});

$loop->installSignal(SIGINT, function ($signo) use ($loop) {
    echo "receive interp signal\n";
    $loop->stop();
});

$loop->poll();