<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueRunner\JobQueue;

use QueueManager\IAdapter;
use QueueRunner\JobQueue;

class HistoryQueue extends JobQueue {

    public function __construct(IAdapter $adapter, $queuename = 'history')
    {
        parent::__construct($queuename, $adapter);
    }
}