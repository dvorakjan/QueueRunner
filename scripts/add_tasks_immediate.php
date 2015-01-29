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

$tasks = [
    [
        'interpreter' => '/usr/bin/bash',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'hello_world.sh',
        'args' => ''
    ],
    [
        'interpreter' => '/usr/bin/bash',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'sleeping.sh',
        'args' => '5'
    ],
    [
        'interpreter' => '/usr/bin/php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'hello_world.php',
        'args' => ''
    ],
    [
        'interpreter' => '/usr/bin/php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'sleeping.php',
        'args' => '5'
    ],
    [
        'interpreter' => '/usr/bin/php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'failed_script.php',
        'args' => ''
    ],
    [
        'interpreter' => '/usr/bin/php -f',
        'basepath' => BASE_PATH.DS.'scripts'.DS.'dummy',
        'script' => 'create_file.php',
        'args' => ''
    ],
];

$dsn = 'mongodb://localhost/queuerunner';

$immediateQueue = new QueueRunner\JobQueue\ImmediateQueue(new QueueManager\Adapter\Mongo($dsn));

foreach($tasks as $task)
{
    $message = new QueueRunner\JobDefinition($task['script'], $task['args'], $task['interpreter'], $task['basepath']);
    $immediateQueue->push($message);
}