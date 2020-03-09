<?php
require __DIR__ . "/../vendor/autoload.php";

$seeds = [
    'www.baidu.com',
    'www.qq.com',
    'www.taobao.com'
];

$loop = \Bobby\StreamEventLoop\LoopFactory::make(\Bobby\StreamEventLoop\LoopFactory::SELECT_LOOP);

foreach ($seeds as $seed) {
    if (!$resource = fsockopen($seed, 80, $errno, $errstr)) {
        exit($errstr);
    };

    stream_set_blocking($resource, false);

    $loop->addLoopStream(\Bobby\StreamEventLoop\LoopContract::WRITE_EVENT, $resource, function ($resource, \Bobby\StreamEventLoop\LoopContract $loop, $flags) use ($seed) {
        $content = "GET / HTTP/1.1\r\n";
        $content .= "Host: $seed\r\n";
        $content .= "Connection: close\r\n\r\n";

        if (!fwrite($resource, $content)) {
            echo "$seed write fail." . PHP_EOL;
            exit(-1);
        } else {
            echo "$seed send request success.\n";
        }

        $loop->removeLoopStream($flags, $resource);

        $loop->addLoopStream(\Bobby\StreamEventLoop\LoopContract::READ_EVENT, $resource, function ($resource, \Bobby\StreamEventLoop\LoopContract $loop, $flags) {
            $data = stream_get_contents($resource);
            echo "Receive data:$data\n";
            $loop->removeLoopStream($flags, $resource);
        });
    });
}

$loop->addWhenWaiting(function () {
    usleep(200000);
    echo "Done action 1 when waiting event.\n";
});

$loop->addWhenWaiting(function () {
    usleep(10000);
    echo "Done action 2 when waiting event.\n";
});

$loop->onCycleStart(function () {
    echo "One cycle start.\n";
});

$loop->onCycleEnd(function () {
    echo "One cycle end.\n";
});

$loop->poll();