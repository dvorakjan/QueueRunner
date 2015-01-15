<?php
/**
 * @author Vojta Biberle
 * @since 13.1.15
 */

require_once __DIR__.'/../../vendor/autoload.php';

$adapter = new QueueManager\DBAdapter\Mongo('mongodb://localhost/database');

$queue = new \QueueManager\Queue('queue', $adapter);

$cnt = $queue->count();
echo 'Cnt: '.$cnt.PHP_EOL;

$msg = new \QueueManager\Message(['name' => 'zprava1']);
$queue->push($msg);
$msg = new \QueueManager\Message(['name' => 'zprava2']);
$queue->push($msg);
$msg = new \QueueManager\Message(['name' => 'zprava3']);
$queue->push($msg);

$cnt = $queue->count();
echo 'Cnt: '.$cnt.PHP_EOL;

$m = $queue->pop();
print_r($m);

$m = $queue->pop();
print_r($m);

$cnt = $queue->count();
echo 'Cnt: '.$cnt.PHP_EOL;

$adapter->drop($queue->getQueueName());

$cnt = $queue->count();
echo 'Cnt: '.$cnt.PHP_EOL;
