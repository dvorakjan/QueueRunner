<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueManager;

class Queue
{

    private $queueName;

    /**
     * @var Adapter\Mongo $adapter
     */
    private $adapter;

    public function __construct($queueName, $DBAdapter)
    {
        $this->queueName = $queueName;
        $this->adapter = $DBAdapter;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function push(IMessage $message)
    {
        if ($message instanceof IMessage) {
            $query = $this->transformMessageToQuery($message);
        }

        $this->adapter->insert($this->queueName, $query);
        return $this->adapter->getLastInsertId();
    }

    public function pop($remove = true)
    {
        $sort = new \stdClass;
        $sort->_id = +1;

        if ($remove) {
            $data =  $this->adapter->findAndModify($this->queueName, [], null, null, ['sort' => $sort, 'remove' => true]);
        } else {
            $data =  $this->adapter->findOne($this->queueName, ['sort' => $sort]);
        }

        return $this->transformDataToMessage($data);
    }

    public function insert($data, $options = [])
    {
        return $this->adapter->insert($this->queueName, $data, $options);
    }

    public function remove($criteria = [], $options = [])
    {
        return $this->adapter->remove($this->queueName, $criteria, $options);
    }

    public function drop()
    {
        return $this->adapter->drop($this->queueName);
    }

    public function update($criteria, $data, $options = [])
    {
        return $this->adapter->update($this->queueName, $criteria, $data, $options);
    }

    public function find($query = [], $fields = [])
    {
        return $this->adapter->find($this->queueName, $query, $fields);
    }

    public function findAndModify($query, $update, $fields, $options)
    {
        return $this->transformDataToMessage($this->adapter->findAndModify($this->queueName, $query, $update, $fields, $options));
    }

    public function count()
    {
        return $this->adapter->count($this->queueName);
    }

    public function isEmpty()
    {
        return $this->count() <= 0;
    }

    private function transformMessageToQuery(IMessage $message)
    {
        $data = $message->toArray();
        $data['__class'] = get_class($message);

        return $data;
    }

    public function transformDataToMessage($data)
    {
        if(empty($data)) return [];

        $class = $data['__class'];
        unset($data['__class']);
        $message = call_user_func_array([$class, 'create'], ['data' => $data]);
        return $message;
    }

}