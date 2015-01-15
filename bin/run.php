<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

define('BASE_PATH', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

ini_set('error_log', BASE_PATH.DS.'logs'.DS.'phpcli');
ini_set('display_errors', 0);

require_once(BASE_PATH.DS.'vendor'.DS.'autoload.php');
require_once(BASE_PATH.DS.'vendor'.DS.'shaneharter'.DS.'php-daemon'.DS.'Core'.DS.'error_handlers.php');


\QueueRunner\Daemon::setFilename(__FILE__);
\QueueRunner\Daemon::getInstance()->run();