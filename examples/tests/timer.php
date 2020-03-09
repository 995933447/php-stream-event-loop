<?php
$eventConfig = new EventConfig();
$eventBase = new EventBase($eventConfig);

$event = new Event($eventBase, -1, Event::TIMEOUT | Event::PERSIST, function($socket, $flag, $custom){
   print_r( $custom );
}, $custom = array(
  'name' => 'bobby',
));

$event2 = new Event($eventBase, -1, Event::TIMEOUT | Event::PERSIST, function() {
    echo microtime(true)." : 歼15，滑跃，起飞！".PHP_EOL;
});

$event3 = Event::timer($eventBase, function () {
    echo microtime(true)." : 敌人已被歼灭1个！".PHP_EOL;
});

$event3->add(1.5);
$event2->add(1);
$event->add(0.5);
$eventBase->loop(EventBase::LOOP_ONCE);