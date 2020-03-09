PHP基于stream函数库开发的事件循环库组件,可并发进行多任务执行。事件循环即event loop，也叫事件驱动IO或者IO多路复用。它提供以监听事件是否达到满足条件从而异步触发事件相关监听器的处理机制。
在event loop模型下可以实现异步IO并发编程、网络并发编程(此处的异步是应用级别的异步，是从应用程序的角度出发的异步)。

并发网络请求并同时进行其他任务示例:
````php
<?php
require __DIR__ . "/../vendor/autoload.php";
use \Bobby\StreamEventLoop\LoopFactory;
use \Bobby\StreamEventLoop\LoopContract;


// 假如$seeds中网址阻塞需消耗时间分别是1s,1.5s,0.5s
$seeds = [
    'www.baidu.com',
    'www.qq.com',
    'www.taobao.com'
];

$loop = LoopFactory::make(LoopFactory::EPOLL_LOOP);

foreach ($seeds as $seed) {
    if (!$resource = fsockopen($seed, 80, $errno, $errstr)) {
        exit($errstr);
    };

    stream_set_blocking($resource, false);

    $loop->addLoopStream(LoopContract::WRITE_EVENT, $resource, function ($resource, LoopContract $loop, $flags) use ($seed) {
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

        $loop->addLoopStream(LoopContract::READ_EVENT, $resource, function ($resource, LoopContract $loop, $flags) {
            $data = stream_get_contents($resource);
            echo "Receive data:$data\n";
            $loop->removeLoopStream($flags, $resource);
        });
    });
}

// 利用等待网络IO的时间做其他事情.
// 此处模拟阻塞200ms
$loop->addWhenWaiting(function (LoopContract $loop) {
    usleep(200000);
    echo "Done action 1 when waiting event.\n";
});

// 利用等待网络IO的时间做其他事情
// 此处模拟阻塞10ms
$loop->addWhenWaiting(function (LoopContract $loop) {
    usleep(10000);
    echo "Done action 2 when waiting event.\n";
});

// 最终完成所有任务时间是1.5s
$loop->poll();
````
服务器编程示例：
````php
<?php
require __DIR__ . "/../vendor/autoload.php";

use Bobby\StreamEventLoop\LoopContract as Loop;
use Bobby\StreamEventLoop\LoopFactory;

$loop = LoopFactory::make();

if ($loop instanceof \Bobby\StreamEventLoop\Select\Loop) {
    echo "Select event loop\n";
} else if ($loop instanceof \Bobby\StreamEventLoop\Epoll\Loop) {
    echo "Epoll event loop\n";
}

$server = stream_socket_server('tcp://0.0.0.0:8080');
stream_set_blocking($server, false);

$loop->addLoopStream(Loop::READ_EVENT, $server, function ($server, Loop $loop, int $eventTypes) {
    $conn = stream_socket_accept($server);
    $data = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nHi";
    $loop->addLoopStream(Loop::WRITE_EVENT, $conn, function ($conn, Loop $loop, int $eventTypes) use ($data) {
        echo "Server receive data " . fread($conn, 1024) . "\n";
        $written = fwrite($conn, $data);

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

 $loop->addAfter(10, function (int $timeId, Loop $loop) {
    echo "remove $timeId\n";
     $loop->removeTimer($timeId);
 });

 $loop->addTick(1, function (int $timerId, Loop $loop) {
     echo "Tick id: $timerId\n";
 });

$loop->installSignal(SIGINT, function ($signo) use ($loop) {
    echo "receive interp signal\n";
    $loop->stop();
});

$loop->poll();
````
除socket流资源还可以使用其他流资源:
````php
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

````
# Bobby\StreamEventLoop\LoopFactory
Loop实例工厂,用于生产实现事件循环契约Bobby\StreamEventLoop\LoopContract接口实现类。

方法列表：
#### Bobby\StreamEventLoop\LoopFactory::make(int $loopType = null)
生成事件循环类实例。生产实现事件循环契约Bobby\StreamEventLoop\LoopContract接口实现类。\
参数：
* **$loopType** 可选参数.是否制定使用的Loop实例。
传入常量LoopFactory::EPOLL_LOOP将返回epoll机制实现的\Bobby\StreamEventLoop\Epoll\Loop实例(需按照libevent以及php-event扩展)。
传入常量 LoopFactory::SELECT_LOOP将返回select实现的\Bobby\StreamEventLoop\Select\Loop实例。不传将根据PHP扩展环境自动从两个实例里面选择适合的实例。
 
 
# Bobby\StreamEventLoop\LoopContract
事件循环契约接口。暴露Loop实现供调用的方法。

方法列表:
#### Bobby\StreamEventLoop\LoopContract::addLoopStream(int $eventType, $stream, callable $callback)
注册一个流事件侦听器，在流准备可读或可写流时触发监听器执行任务。\
参数:
* **$eventType** LoopContract::READ_EVENT或者LoopContract::WRITE_EVENT或者LoopContract::READ_EVENT | LoopContract::WRITE_EVENT,代表注册监听可读事件或者监听可写事件或者二者都监听。
* **$stream** 要监听的流资源。
* **$callback** 监听器。当流事件准备好时会执行该回调。Loop实例将自动注入流资源,Loop实例自身以及事件类型到函数中供函数使用,如下所示：\
function (stream, \Bobby\StreamEventLoop\LoopContract $loop, int $flags) {...}
示例：
````php
$loop->addLoopStream(\Bobby\StreamEventLoop\LoopContract::READ_EVENT, $fp, function ($fp, \Bobby\StreamEventLoop\LoopContract $loop, int $flags) {
    stream_set_blocking($fp, 0);
    $data = stream_get_contents($fp);
    echo "poll.php contents:$data\n";
    $loop->removeLoopStream($flags, $fp);
});
````

#### Bobby\StreamEventLoop\LoopContract::removeLoopStream(int $eventType, $stream)
从事件循环中取消已监听的流事件。\
参数：
* **$eventType** LoopContract::READ_EVENT或者LoopContract::WRITE_EVENT或者LoopContract::READ_EVENT | LoopContract::WRITE_EVENT,代表取消监听可读事件或者取消监听可写事件或者二者都取消监听。
* **$stream** 要移除已监听的流资源。

#### Bobby\StreamEventLoop\LoopContract::installSignal(int $signalNo, callable $callback)
在事件循环中安装信号处理器\
参数:
* **$signalNo** 要监听的信号
* **$callback** 信号处理回调。当信号发送时将触发该回调

#### Bobby\StreamEventLoop\LoopContract::removeSignal(int $signalNo)
取消已在时间循环中安装的信号处理器\
参数:
* **$signalNo** 信号

#### Bobby\StreamEventLoop\LoopContract::addTick(float $interval, callable $callback): int
在时间循环中添加持续定时器(毫秒级)，返回定时器ID。相当于js的setInterval函数\
参数:
* **$interval** 触发秒数间隔，传入小数可精确到毫秒。
* **$callback** 定时器触发时执行的回调。

#### Bobby\StreamEventLoop\LoopContract::addAfter(float $interval, callable $callback): int
在时间循环中添加一次性执行定时器(毫秒级)，返回定时器ID。和addTick不同的是该定时只执行一次之后便会失效。相当于js的setTimeout函数\
参数:
* **$interval** 触发秒数间隔，传入小数可精确到毫秒。
* **$callback** 定时器触发时执行的回调。Loop实例将自动注入定时器ID,Loop实例自身到函数参数中供函数使用，如下所示:
function (int $timerId, \Bobby\StreamEventLoop\LoopContract $loop) {...}\
示例:
````php
 $loop->addAfter(10, function (int $timeId, \Bobby\StreamEventLoop\LoopContract $loop) {
    echo "remove $timeId\n";
    $loop->removeTimer($timeId);
 });
````

#### Bobby\StreamEventLoop\LoopContract::removeTimer(int $timerId)
移除已安装的定时器。\
参数：
* **$timerId** 定时器ID

#### Bobby\StreamEventLoop\LoopContract::addWhenWaiting(callable $callback)
将$callback当做为任务加入等待事件时执行程序队列。当没有事件准备好时, 利用闲置时间执行额外任务.\
参数：
* **$callback** 当没有事件准备好时, 利用等待事件准备的时间执行该回调.Loop实例将自动注入自己到回调供函数使用：\
````php
 $loop->addWhenWaiting(function (\Bobby\StreamEventLoop\LoopContract $loop) {
    // TODO...
 });
````

#### Bobby\StreamEventLoop\LoopContract::poll()
开始事件循环。将阻塞执行事件循环直至时间循环中没有要监听的事件(包括流事件,信号处理器以及定时器事件)。

#### Bobby\StreamEventLoop\LoopContract::stop()
暂停事件循环并退出阻塞。

#### Bobby\StreamEventLoop\LoopContract::onCycleStart($callback)
一轮事件循环之前会触发该回调函数。null代表取消回调事件。

#### Bobby\StreamEventLoop\LoopContract::onCycleEnd($callback)
一轮事件循环之后会触发该回调函数。null代表取消回调事件。