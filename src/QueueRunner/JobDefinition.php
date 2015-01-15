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
     */
    public function setInterpreter($interpreter)
    {
        $this->interpreter = $interpreter;
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
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
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
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;
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
     */
    public function setArgs($args)
    {
        $this->args = $args;
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
     */
    public function setNice($nice)
    {
        $this->nice = $nice;
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
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     */
    public function setNextRun($nextRun)
    {
        $this->nextRun = $nextRun;
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
     */
    public function setStarted($started)
    {
        $this->started = $started;
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
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
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
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
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
     */
    public function setOutput($output)
    {
        $this->output = $output;
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
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
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
     */
    public function setErrcode($errcode)
    {
        $this->errcode = $errcode;
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
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
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
     */
    public function setRepetitive($repetitive)
    {
        $this->repetitive = $repetitive;
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
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
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
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function addTag($tag)
    {
        $this->tags[] = $tag;
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