<?php
///**
// * @Author: Bobby
// * @Date:   2018-12-28 11:32:55
// * @Last Modified by:   Bobby
// * @Last Modified time: 2018-12-28 11:41:25
// */
$eventConfig = new EventConfig();
$base = new EventBase($eventConfig);
$features = $base->getFeatures();
echo '事件位掩码:' . $features . PHP_EOL;
if ($features & EventConfig::FEATURE_ET) {
    echo '边缘触发' . PHP_EOL;
}
if ($features & EventConfig::FEATURE_O1) {
    echo '添加删除事件' . PHP_EOL;
}
if ($features & EventConfig::FEATURE_FDS) {
    echo '任意文件描述符,不仅仅是socket' . PHP_EOL;
}

echo "===" . PHP_EOL;

$eventConfig = new EventConfig();
$eventConfig->requireFeatures(EventConfig::FEATURE_FDS);
$eventConfig->requireFeatures(EventConfig::FEATURE_ET);
//$eventConfig->requireFeatures(EventConfig::FEATURE_O1);
$base = new EventBase($eventConfig);
$features = $base->getFeatures();
echo '事件位掩码:' . $features . PHP_EOL;
if ($features & EventConfig::FEATURE_ET) {
    echo '边缘触发' . PHP_EOL;
}
if ($features & EventConfig::FEATURE_O1) {
    echo '添加删除事件' . PHP_EOL;
}
if ($features & EventConfig::FEATURE_FDS) {
    echo '任意文件描述符,不仅仅是socket' . PHP_EOL;
}