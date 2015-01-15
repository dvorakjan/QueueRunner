<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueManager;

/**
 * This is very very generic implementation of IMessage, usually you will made some private attributes of class and made
 * proper export function for Adapter.
 *
 * @author Vojta Biberle
 *
 * Class Message
 * @package QueueManager
 */
class Message implements IMessage {

    private $data;

    public function __construct($data)
    {
        if(!is_array($data))
        {
            throw new \RuntimeException('Bad argument type. Only array is accepted.');
        }
        $this->data = $data;
    }

    public static function create($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return $this->data;
    }
}