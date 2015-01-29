<?php
/**
 * @author Vojta Biberle
 * @since 13.1.15
 */

namespace QueueRunner\Worker;

use QueueRunner\JobDefinition;
use QueueRunner\JobQueue;
use QueueRunner\JobQueue\ImmediateQueue;
use QueueRunner\JobQueue\PlannedQueue;
use QueueRunner\Worker;

class PlannedWorker implements \Core_IWorker
{

    /**
     * Provided Automatically
     * @var \Core_Worker_Mediator
     */
    public $mediator;

    /**
     * @var JobQueue $planned
     */
    private $planned = null;

    /**
     * @var JobQueue $immediate
     */
    private $immediate = null;

    /**
     * @param JobQueue $planned
     */
    public function setPlanned(PlannedQueue $planned)
    {
        $this->planned = $planned;
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
        if (is_null($this->planned)) {
            $this->mediator->fatal_error('Planned worker: Planned queue is not set!');
        }

        if (is_null($this->immediate)) {
            $this->mediator->fatal_error('Planned worker: Immediate queue is not set!');
        }
    }

    public function moveToImmediate()
    {
        $this->mediator->log('Planned worker: Planning next jobs ...');

        $user = $this->mediator->daemon('user');
        $group = $this->mediator->daemon('group');
        $this->mediator->log('Planned worker: Setting process UIT: '.$user.' and GID: '.$group.' ...');

        $userInfo = posix_getpwnam($user);
        posix_seteuid($userInfo['uid']);

        $groupInfo = posix_getgrnam($group);
        posix_setegid($groupInfo['gid']);

        try {
            $cursor = $this->planned->find([]);

            foreach ($cursor as $document) {
                $jobdefinition = JobDefinition::create($document);

                if ($jobdefinition->isDue()) {
                    $this->immediate->push($jobdefinition);
                    if (!$jobdefinition->isRepetitive()) {
                        $this->planned->remove(['_id' => $jobdefinition->_id]);
                        $this->mediator->log('Planned worker: Job moved to immediate: ' . $jobdefinition);
                    } else {
                        $this->mediator->log('Planned worker: Job stais in planned for future run: ' . $jobdefinition);
                    }
                }
            }
        }catch(\Exception $e){
            $this->mediator->fatal_error($e->getMessage());
        }
    }
}