<?php
/**
 * @author Vojta Biberle
 * @since 28.1.15
 */

try {
    define('BASE_PATH', dirname(dirname(dirname(__FILE__))));
    define('DS', DIRECTORY_SEPARATOR);

    $content = 'Hello Word';

    $file = file_put_contents(BASE_PATH . DS . 'logs' . DS . 'hello.txt', $content);

    exit(0);
}
catch(\Exception $e)
{
    print_r($e->getMessage());
    exit(1);
}
