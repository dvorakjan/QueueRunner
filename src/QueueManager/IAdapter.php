<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueManager;

interface IAdapter {
    public function __construct($dsn, $dataDatabase);
    public function connect();
}