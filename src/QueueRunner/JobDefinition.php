<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueRunner;

use QueueManager\IMessage;

class JobDefinition implements IMessage {

    private $interpreter = '';
    private $basePath = '';
    private $executable = '';
    private $args = '';
    private $nice = null;
    private $status = JobStatus::PLANED;
    private $nextRun = null;
    private $started = null;
    private $finished = null;
    private $pid = null;
    private $output = null;
    private $errors = null;
    private $errcode = null;
    private $schedule = null;
    private $repetitive = true;
    private $priority = null;
    private $tags = [];

    function __construct($executable, $args = null, $interpreter = '', $basePath = '', $nice = null, $schedule = '', $repetitive = true, $priority = null, $tags = [])
    {
        $this->interpreter = $interpreter;
        $this->basePath = $basePath;
        $this->executable = $executable;
        $this->args = $args;
        $this->nice = $nice;
        $this->schedule = $schedule;
        $this->repetitive = $repetitive;
        $this->priority = $priority;
        $this->tags = $tags;
    }

    /**
     * @return mixed
     */
    public function getInterpreter()
    {
        return $this->interpreter;
    }

    /**
     * @param mixed $interpreter
     * @return $this
     */
    public function setInterpreter($interpreter)
    {
        $this->interpreter = $interpreter;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param mixed $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExecutable()
    {
        return $this->executable;
    }

    /**
     * @param mixed $executable
     * @return $this
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $args
     * @return $this
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNice()
    {
        return $this->nice;
    }

    /**
     * @param mixed $nice
     * @return $this
     */
    public function setNice($nice)
    {
        $this->nice = $nice;
        return $this;
    }

    /**
     * @return JobStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNextRun()
    {
        return $this->nextRun;
    }

    /**
     * @param mixed $nextRun
     * @return $this
     */
    public function setNextRun($nextRun)
    {
        $this->nextRun = $nextRun;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * @param mixed $started
     * @return $this
     */
    public function setStarted($started)
    {
        $this->started = $started;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFinished()
    {
        return $this->finished;
    }

    /**
     * @param mixed $finished
     * @return $this
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param mixed $pid
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return null
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param null $output
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param mixed $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return null
     */
    public function getErrcode()
    {
        return $this->errcode;
    }

    /**
     * @param null $errcode
     * @return $this
     */
    public function setErrcode($errcode)
    {
        $this->errcode = $errcode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @param mixed $schedule
     * @return $this
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isRepetitive()
    {
        return $this->repetitive;
    }

    /**
     * @param boolean $repetitive
     * @return $this
     */
    public function setRepetitive($repetitive)
    {
        $this->repetitive = $repetitive;
        return $this;
    }

    public function isDue()
    {
        $cron = \Cron\CronExpression::factory($this->schedule);
        return $cron->isDue();
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }

    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    public function unsetId() {
        unset($this->_id);
    }

    public static function create($data)
    {
        if(!isset($data['executable']))
            return false;

        $msg = new self($data['executable']);
        foreach($data as $key=>$value)
        {
            $method = 'set'.ucfirst($key);
            if(method_exists($msg, $method))
            {
                call_user_func([$msg, $method], $value);
            }
            else
            {
                $msg->{$key} = $value;
            }
        }
        return $msg;
    }

    public function toArray()
    {
        $atributes = get_object_vars($this);

        return $atributes;
    }

    public function __toString()
    {
        $result = '';

        $result .= is_int($this->nice) ? 'nice -n '.$this->nice.' ': '';
        $result .= $this->interpreter.' ';
        $result .= is_string($this->basePath) ? $this->basePath.'/' : '';
        $result .= $this->executable;
        $result .= is_string($this->args) ? ' '.$this->args : '';

        return $result;
    }
}