<?php
/**
 * @author Vojta Biberle
 * @since 28.1.15
 */

$command = '/usr/bin/php -f /vagrant/QueueRunner/scripts/dummy/create_file.php 2>&1';

exec($command, $output, $retval);

echo 'OUTPUT'.PHP_EOL;
print_r($output);

echo PHP_EOL.PHP_EOL;

echo 'RETVAL'.PHP_EOL;
print_r($retval);