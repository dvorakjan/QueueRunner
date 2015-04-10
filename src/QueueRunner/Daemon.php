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

    public $user = 'root';
    public $group = 'root';

    private $logfilePath;

    private $lastHistoryRun = null;
    private $lastPlannedRun = null;
    private $intervalForPlanning = 60;

    protected function getopt()
    {
        parent::getopt();
        $opts = getopt('dp:s:');

        if (empty($opts)) {
            $this->fatal_error('getopt: this function sometimes work "funny", just add parameters from \\Core_Daemon and "s:" at end to repair it');
        }

        if (isset($opts['s'])) {
            $this->settingsPath = $opts['s'];
        } else {
            $this->settingsPath = BASE_PATH . DS . 'support' . DS . 'etc' . DS . 'default' . DS . 'QueueRunner.ini';
        }
    }

    // temporary solution because of bug in Core_Daemon::restart()
    public function restart()
    {
        if ($this->is_parent == false)
            return;
 
        $this->shutdown = true;
        $this->log('Restart Happening Now...');
        foreach(array_merge($this->workers, $this->plugins) as $object) {
            $this->{$object}->teardown();
            unset($this->{$object});
        }
 
        $this->callbacks = array();
 
        // Close the resource handles to prevent this process from hanging on the exec() output.
        if (is_resource(STDOUT)) fclose(STDOUT);
        if (is_resource(STDERR)) fclose(STDERR);
        if (is_resource(STDIN))  fclose(STDIN);
        //exec($this->getFilename());
        exec('systemctl restart queuerunner > /dev/null &');
 
        // A new daemon process has been created. This one will stick around just long enough to clean up the worker processes.
        exit();
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

        if(isset($this->settings['worker']['user'])) {
            $this->user = $this->settings['worker']['user'];
        }
        if(isset($this->settings['worker']['group'])) {
            $this->group = $this->settings['worker']['group'];
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
                $this->log('Event Loop Iteration: Can\'t run Planned worker, no workers available.');
            }
        } else {
            $this->log('Event Loop Iteration: Planned worker wont be run, time criteria not met (run only 1 per minute).');
        }

    	// try to run command from immediate queue for every idle worker
    	$idleWorkersCount = $this->workers - $this->Immediate->running_count();
    	if ($idleWorkersCount > 0) {
	        for ($i = 0; $i < $idleWorkersCount; $i++) {
	            $this->Immediate->runNextCommand();
	            usleep(10000); // without this, after aprox. 5 minutes "Message Encode Failed" errors will appear until restart
	        }
	    } else {
    		$this->log('Event Loop Iteration: Can\'t run Immediate worker, no workers available.');
	    }

        if (is_null($this->lastHistoryRun) || ($now - $this->lastHistoryRun > $this->intervalForPlanning)) {
            if ($this->History->is_idle()) {
                $this->History->moveToHistory();
                $this->lastHistoryRun = time();
            } else {
                $this->log('Event Loop Iteration: Can\'t run History worker, no workers available.');
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
