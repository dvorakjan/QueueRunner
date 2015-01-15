<?php
/**
 * @author Vojta Biberle
 * @since 14.1.15
 */

define('BASE_PATH', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

ini_set('error_log', BASE_PATH.DS.'logs'.DS.'phpcli');
ini_set('display_errors', 0);

require_once(BASE_PATH.DS.'vendor'.DS.'autoload.php');
require_once(BASE_PATH.DS.'vendor'.DS.'shaneharter'.DS.'php-daemon'.DS.'Core'.DS.'error_handlers.php');

$date = new \DateTime();
$date->add(new \DateInterval('P1M'));
$minute = $date->format('i');
$hour = $date->format('G');

$cron = $minute.' '.$hour.' * * *';
echo 'Cron: '.$cron.PHP_EOL;

$tasks = [
    [
        'interpreter' => 'bash',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'hello_world.sh',
        'args' => '',
        'schedule' => $cron
    ],
    [
        'interpreter' => 'bash',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'sleeping.sh',
        'args' => '5',
        'schedule' => $cron
    ],
    [
        'interpreter' => 'php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'hello_world.php',
        'args' => '',
        'schedule' => $cron
    ],
    [
        'interpreter' => 'php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'sleeping.php',
        'args' => '5',
        'schedule' => $cron
    ],
    [
        'interpreter' => 'php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'failed_script.php',
        'args' => '',
        'schedule' => $cron
    ],
];

$dsn = 'mongodb://localhost/queuerunner';

$immediateQueue = new QueueRunner\JobQueue\PlannedQueue(new QueueManager\Adapter\Mongo($dsn));

foreach($tasks as $task)
{
    $message = new QueueRunner\JobDefinition($task['script'], $task['args'], $task['interpreter'], $task['basepath'], null, $task['schedule']);
    $immediateQueue->push($message);
}