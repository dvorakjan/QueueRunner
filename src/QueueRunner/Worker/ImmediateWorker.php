<?php
/**
 * @author Vojta Biberle
 * @since 13.1.15
 */

namespace QueueRunner\Worker;


use QueueRunner\JobQueue\ImmediateQueue;
use QueueRunner\JobStatus;
use QueueRunner\Worker;

class ImmediateWorker implements \Core_IWorker
{

    /**
     * Provided Automatically
     * @var \Core_Worker_Mediator
     */
    public $mediator;

    /**
     * @var JobQueue $immediate
     */
    private $immediate = null;

    /**
     * @param JobQueue $immediate
     */
    public function setImmediate(ImmediateQueue $immediate)
    {
        $this->immediate = $immediate;
    }

    /**
     * Called on Construct or Init
     * @return void
     */
    public function setup()
    {
        // TODO: Implement setup() method.
    }

    /**
     * Called on Destruct
     * @return void
     */
    public function teardown()
    {
        // TODO: Implement teardown() method.
    }

    /**
     * This is called during object construction to validate any dependencies
     * @return Array    Return array of error messages (Think stuff like "GD Library Extension Required" or
     *                  "Cannot open /tmp for Writing") or an empty array
     */
    public function check_environment()
    {
        if (is_null($this->immediate)) {
            $this->mediator->fatal_error('Immediate worker: Immediate queue is not set!');
        }
    }

    public function runNextCommand()
    {
        $this->mediator->log('Immediate worker: Running next job ...');

        $user = $this->mediator->daemon('user');
        $group = $this->mediator->daemon('group');
        $this->mediator->log('Immediate worker: Setting process UID: '.$user.' and GID: '.$group.' ...');

        $userInfo = posix_getpwnam($user);
        posix_seteuid($userInfo['uid']);

        $groupInfo = posix_getgrnam($group);
        posix_setegid($groupInfo['gid']);

        try {
            $pid = getmypid();

            $message = $this->immediate->findAndModify(['status' => JobStatus::PLANED], ['$set' => ['status' => JobStatus::RUNNING, 'pid' => $pid]], [], ['sort' => ['nextRun' => 1, 'priority' => 1]]);

            //var_dump($message);

            if (!empty($message)) {
                //$this->mediator->log('Immediate worker: Executing command: ' . $message);

                $command = '';
                $command .= !is_null($message->getNice()) ? 'nice -n ' . $message->getNice() . ' ' : '';
                $command .= !is_null($message->getInterpreter()) ? $message->getInterpreter().' ' : ' ';
                $command .= !is_null($message->getBasePath()) ? ' ' . $message->getBasePath() . DS : '';
                $command .= $message->getExecutable();
                $command .= !is_null($message->getArgs()) ? ' ' . $message->getArgs() : '';
                //$command .= ' 2>&1';

                $this->mediator->log('Immediate worker: Executing command: ' . $command);

                $started = time();
                $descriptorspec = array(
                    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                    2 => array("pipe", "w") // stderr is a file to write to
                );

                $process = proc_open($command, $descriptorspec, $pipes, ($message->getBasePath() ? $message->getBasePath() : null) );

                if (is_resource($process)) {

                    $output = stream_get_contents($pipes[1]);
                    $error = stream_get_contents($pipes[2]);

                    $retval = proc_close($process);
                }

                $finished = time();

                $this->mediator->log('Immediate worker: Output: ' . json_encode($output));
                $this->mediator->log('Immediate worker: Retval: ' . json_encode($retval));

                if ($retval == 0) {
                    $status = JobStatus::SUCCESS;
                    $errcode = null;
                } else {
                    $status = JobStatus::FAILED;
                    $errcode = $retval;
                }

                $this->immediate->update(
                    ['_id' => $message->_id],
                    ['$set' => [
                        'status' => $status,
                        'output' => $output,
                        'errors' => $error,
                        'errcode' => $errcode,
                        'started' => $started,
                        'finished' => $finished,
                        'executedCommand' => $command
                    ]]);
            } else {
                $this->mediator->log('Immediate worker: Nothing to run.');
            }
        } catch (\Exception $e) {
            $this->mediator->fatal_error($e->getMessage());
        }
    }
}