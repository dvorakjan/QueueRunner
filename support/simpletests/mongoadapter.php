<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

require_once __DIR__.'/../../vendor/autoload.php';

$adapter = new QueueManager\DBAdapter\Mongo('mongodb://localhost/database');

$adapter->insert('collection', ['name' => 'objekt1']);
$adapter->insert('collection', ['name' => 'objekt2']);
$adapter->insert('collection', ['name' => 'objekt3']);
$adapter->insert('collection', ['name' => 'objekt4']);

$cursor = $adapter->find('collection');

$i=1;
foreach($cursor as $obj)
{
    echo "Obj$i:".PHP_EOL;
    print_r($obj);
    $i++;
}

echo "Count: ".$adapter->count('collection').PHP_EOL;

$adapter->drop('collection');


echo "Count: ".$adapter->count('collection').PHP_EOL;