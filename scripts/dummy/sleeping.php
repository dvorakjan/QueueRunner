<?php
/**
 * @author Vojta Biberle
 * @since 14.1.15
 */

$sleep = $argv[1];

echo "zzzZZZzzz for $sleep seconds";
sleep($sleep);
echo PHP_EOL;