<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueRunner;

class JobStatus {
    const PLANED = 'planed';
    const RUNNING = 'running';
    const SUCCESS = 'success';
    const FAILED = 'failed';
}