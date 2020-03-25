<?php
require __DIR__ . "/../vendor/autoload.php";

$loop = \Bobby\StreamEventLoop\LoopFactory::make();

$fp = fopen("poll.php", 'r');

$loop->addLoopStream(\Bobby\StreamEventLoop\LoopContract::READ_EVENT, $fp, function ($fp, \Bobby\StreamEventLoop\LoopContract $loop, int $flags) {
    stream_set_blocking($fp, 0);
    $data = stream_get_contents($fp);
    echo "poll.php contents:$data\n";
    $loop->removeLoopStream($flags, $fp);
});

$fp2 = fopen("curl.php", 'r');

$loop->addLoopStream(\Bobby\StreamEventLoop\LoopContract::READ_EVENT, $fp2, function ($fp, \Bobby\StreamEventLoop\LoopContract $loop, int $flags) {
    stream_set_blocking($fp, 0);
    $data = stream_get_contents($fp);
    echo "curl.php contents:$data\n";
    $loop->removeLoopStream($flags, $fp);
});

$loop->poll();