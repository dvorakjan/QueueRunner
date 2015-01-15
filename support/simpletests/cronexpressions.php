<?php
/**
 * @author Vojta Biberle
 * @since 13.1.15
 */

require_once __DIR__.'/../../vendor/autoload.php';

$time1400 = new \DateTime('14:00');

$time1430 = new \DateTime('14:30');

$cron = \Cron\CronExpression::factory('0 14 * * *');

if($cron->isDue('14:00'))
{
    echo 'Run'.PHP_EOL;
}
else
{
    echo 'Waiting'.PHP_EOL;
}

//$cron = \Cron\CronExpression::factory('2 14 * * *');

if($cron->isDue('14:30'))
{
    echo 'Run'.PHP_EOL;
}
else
{
    echo 'Waiting'.PHP_EOL;
}