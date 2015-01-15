<?php
/**
 * @author Vojta Biberle
 * @since 13.1.15
 */

namespace QueueRunner\Worker;


use QueueRunner\JobDefinition;
use QueueRunner\JobQueue;
use QueueRunner\JobQueue\HistoryQueue;
use QueueRunner\JobQueue\ImmediateQueue;
use QueueRunner\JobStatus;
use QueueRunner\Worker;

class HistoryWorker implements \Core_IWorker
{
    /**
     * Provided Automatically
     * @var \Core_Worker_Mediator
     */
    public $mediator;

    /**
     * @var JobQueue $history
     */
    private $history = null;

    /**
     * @var JobQueue $runned
     */
    private $immediate = null;

    /**
     * @param JobQueue $history
     */
    public function setHistory(HistoryQueue $history)
    {
        $this->history = $history;
    }

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
        if (is_null($this->history)) {
            $this->mediator->fatal_error('History worker: History queue is not set!');
        }

        if (is_null($this->immediate)) {
            $this->mediator->fatal_error('History worker: Immediate queue is not set!');
        }
    }

    public function moveToHistory()
    {
        $this->mediator->log('History worker: Moving SUCCESS and FAILED jobs to history.');

        try {
            $cursor = $this->immediate->find(['status' => ['$in' => [JobStatus::SUCCESS, JobStatus::FAILED]]]);

            foreach ($cursor as $document) {
                $this->immediate->remove(['_id' => $document['_id']]);
                $this->history->insert($document);
                $this->mediator->log('History worker: Message moved: ' . JobDefinition::create($document));
            }
        } catch (\Exception $e) {
            $this->mediator->fatal_error($e->getMessate());
        }
    }
}