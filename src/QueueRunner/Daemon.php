<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueRunner;

use Exception;
use QueueManager\Adapter\Mongo;
use QueueRunner\JobQueue\HistoryQueue;
use QueueRunner\JobQueue\ImmediateQueue;
use QueueRunner\JobQueue\PlannedQueue;
use QueueRunner\Worker\HistoryWorker;
use QueueRunner\Worker\ImmediateWorker;
use QueueRunner\Worker\PlannedWorker;

class Daemon extends \Core_Daemon
{

    protected $loop_interval = 2;

    private $workers = 4;

    private $worker_timeout = 3600;

    private $settingsPath;

    private $dsn = 'mongodb://localhost/queuerunner';

    private $history_name = 'history';
    private $immediate_name = 'immediate';
    private $planned_name = 'planned';

    private $logfilePath;

    private $lastHistoryRun = null;
    private $lastPlannedRun = null;
    private $intervalForPlanning = 60;

    protected function getopt()
    {
        parent::getopt();
        $opts = getopt('dp:s:');

        if (empty($opts)) {
            $this->fatal_error('getopt: this function sometimes fork "funny", just add parameters from \\Core_Daemon to repair it');
        }

        if (isset($opts['s'])) {
            $this->settingsPath = $opts['s'];
        } else {
            $this->settingsPath = BASE_PATH . DS . 'support' . DS . 'etc' . DS . 'default' . DS . 'QueueRunner.ini';
        }
    }

    protected function setup_plugins()
    {
        //$this->plugin('Lock_File'); //TODO: Lock plugins don't work - Lock file just write log, Shm do nothing.

        $this->plugin('settings', new \Core_Plugin_Ini());
        $this->settings->filename = $this->settingsPath;
        //$this->settings->required_sections = array('daemon', 'queues');
        $this->settings->setup(); //HACK: I need this plugin initialized in setup_workers() method.
        if (isset($this->settings['daemon']['logfile_path'])) {
            $this->logfilePath = $this->settings['daemon']['logfile_path'];
        } else {
            $this->logfilePath = BASE_PATH . DS . 'logs' . DS . 'queuerunner.log';
        }
    }

    protected function setup_workers()
    {
        /*$this->worker('Worker', new Worker());
        $this->Worker->workers(4);

        $this->Worker->timeout(8);
        $this->Worker->onTimeout(function($call, $log){
            $log("API Timeout Reached");
        });*/

        if (isset($this->settings['queues']['dsn'])) {
            $this->dsn = $this->settings['queues']['dsn'];
        }

        $mongo = new Mongo($this->dsn);

        if (isset($this->settings['queues']['history_name'])) {
            $this->history_name = $this->settings['queues']['history_name'];
        }
        if (isset($this->settings['queues']['immediate_name'])) {
            $this->immediate_name = $this->settings['queues']['immediate_name'];
        }
        if (isset($this->settings['queues']['planned_name'])) {
            $this->planned_name = $this->settings['queues']['planned_name'];
        }

        $historyQueue = new HistoryQueue($mongo, $this->history_name);
        $immediateQueue = new ImmediateQueue($mongo, $this->immediate_name);
        $plannedQueue = new PlannedQueue($mongo, $this->planned_name);

        $historyWorker = new HistoryWorker();
        $historyWorker->setHistory($historyQueue);
        $historyWorker->setImmediate($immediateQueue);

        $immediateWorker = new ImmediateWorker();
        $immediateWorker->setImmediate($immediateQueue);

        $plannedWorker = new PlannedWorker();
        $plannedWorker->setPlanned($plannedQueue);
        $plannedWorker->setImmediate($immediateQueue);

        $this->worker('History', $historyWorker);
        $this->worker('Immediate', $immediateWorker);
        $this->worker('Planned', $plannedWorker);

        if (isset($this->settings['daemon']['workers'])) {
            $this->workers = (int)$this->settings['daemon']['workers'];
        }

        $this->History->workers(1);
        $this->Immediate->workers($this->workers);
        $this->Planned->workers(1);

        if (isset($this->settings['daemon']['worker_timeout'])) {
            $this->worker_timeout = (int)$this->settings['daemon']['worker_timeout'];
        }

        $this->Immediate->timeout($this->worker_timeout);
        $this->Immediate->onTimeout(function ($call, $log) use ($immediateQueue) {
            $log('Error: Some Immediate worker timeouted. Try search {status: running} on immediate collection.');
        });
    }

    /**
     * The setup method will contain the one-time setup needs of the daemon.
     * It will be called as part of the built-in init() method.
     * Any exceptions thrown from setup() will be logged as Fatal Errors and result in the daemon shutting down.
     * @return void
     * @throws Exception
     */
    protected function setup()
    {
        if ($this->intervalForPlanning < $this->loop_interval) {
            $this->log('Daemon Setup Warning: Interval for planning and history moves are smaller than Loop interval. Planning and history moves will be more delayed!');
        }
        if ($this->intervalForPlanning != 60) {
            $this->log('Daemon Setup Warning: Interval for planning and history moves should be 60 seconds exactly!');
        }

        if (isset($this->settings['daemon']['loop_interval'])) {
            $this->loop_interval = $this->settings['daemon']['loop_interval'];
        }

        $that = $this;
        $this->on(\Core_Daemon::ON_SIGNAL, function ($signal) use ($that) {
            if (isset($that->settings['signals'][$signal])) {
                $action = $that->settings['signals'][$signal];
                switch ($action) {
                    case 'plan_tasks':
                        $that->log('On signal ' . $signal . ': Planning processes');
                        $that->lastPlannedRun = time();
                        break;
                    case 'clean_history':
                        $that->log('On signal ' . $signal . ': Cleaning history');
                        $that->lastHistoryRun = time();
                        break;
                }
            }
        });
    }

    /**
     * The execute method will contain the actual function of the daemon.
     * It can be called directly if needed but its intention is to be called every iteration by the ->run() method.
     * Any exceptions thrown from execute() will be logged as Fatal Errors and result in the daemon attempting to restart or shut down.
     *
     * @return void
     * @throws Exception
     */
    protected function execute()
    {
        $now = time();
        if (is_null($this->lastPlannedRun) || ($now - $this->lastPlannedRun > $this->intervalForPlanning)) {
            if ($this->Planned->is_idle()) {
                $this->Planned->moveToImmediate();
                $this->lastPlannedRun = time();
            } else {
                $this->log('Event Loop Iteration: Can\'not run Planned worker, no workers available.');
            }
        } else {
            $this->log('Event Loop Iteration: Planned worker wont be run, time criteria not met (run only 1 per minute).');
        }

        if ($this->Immediate->is_idle()) {
            $this->Immediate->runNextCommand();
        } else {
            $this->log('Event Loop Iteration: Can\'not run Immediate worker, no workers available.');
        }

        if (is_null($this->lastHistoryRun) || ($now - $this->lastHistoryRun > $this->intervalForPlanning)) {
            if ($this->History->is_idle()) {
                $this->History->moveToHistory();
                $this->lastHistoryRun = time();
            } else {
                $this->log('Event Loop Iteration: Can\'not run History worker, no workers available.');
            }
        } else {
            $this->log('Event Loop Iteration: History worker wont be run, time criteria not met (run only 1 per minute).');
        }
    }

    /**
     * Return a log file name that will be used by the log() method.
     *
     * You could hard-code a string like '/var/log/myapplog', read an option from an ini file, create a simple log
     * rotator using the date() method, etc
     *
     * Note: This method will be called during startup and periodically afterwards, on even 5-minute intervals: If you
     *       start your application at 13:01:00, the next check will be at 13:05:00, then 13:10:00, etc. This periodic
     *       polling enables you to build simple log rotation behavior into your app.
     *
     * @return string
     */
    protected function log_file()
    {
        return $this->logfilePath;
    }
}