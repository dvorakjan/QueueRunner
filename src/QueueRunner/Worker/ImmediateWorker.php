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

        try {
            $pid = getmypid();

            $message = $this->immediate->findAndModify(['status' => JobStatus::PLANED], ['$set' => ['status' => JobStatus::RUNNING, 'pid' => $pid]], [], ['sort' => ['nextRun' => 1, 'priority' => 1]]);

            //var_dump($message);

            if (!empty($message)) {
                $this->mediator->log('Immediate worker: Executing command: ' . $message);

                $command = '';
                $command .= !is_null($message->getNice()) ? 'nice -n ' . $message->getNice() . ' ' : '';
                $command .= !is_null($message->getInterpreter()) ? $message->getInterpreter() : '';
                $command .= !is_null($message->getBasePath()) ? ' ' . $message->getBasePath() . DS : '';
                $command .= $message->getExecutable();
                $command .= !is_null($message->getArgs()) ? ' ' . $message->getArgs() : '';

                $started = time();

                exec($command, $output, $retval);


                $finished = time();

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