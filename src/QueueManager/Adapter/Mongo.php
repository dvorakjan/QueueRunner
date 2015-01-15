<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueManager\Adapter;

use QueueManager\IAdapter;
use QueueManager\Utils;

class Mongo implements IAdapter {

    private $dsn = null;

    /**
     * @var \MongoClient $client
     */
    private $client = null;

    /**
     * @var \MongoDB $collection
     */
    private $db = null;

    /**
     * @var \MongoCollection $collection
     */
    private $collection = null;

    /**
     * @param string $dsn
     */
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }

    /**
     * Method must make connection to DB, when disconnect.
     *
     * @author Vojta Biberle
     *
     */
    public function connect()
    {
        if(is_null($this->client))
        {
            $this->client = new \MongoClient($this->dsn);
            $parsedDsn = Utils::ParseDsn($this->dsn);
            $this->db = $this->client->{$parsedDsn['database']};
            //$this->collection = $this->client->selectCollection($this->dsn['database'], $this->dsn['table']);
        }
    }

    /*public function setCollection($name)
    {
        $this->collection = $this->db->{$name};
    }*/

    /**
     * Check if adapter is connected.
     *
     * @author Vojta Biberle
     *
     * @return bool
     */
    public function isConnected()
    {
        return !is_null($this->client);
    }

    public function insert($collection, $data, $options = [])
    {
        $this->connect();
        return $this->db->{$collection}->insert($data, $options);
    }

    public function update($collection, $criteria, $data, $options = [])
    {
        $this->connect();
        return $this->db->{$collection}->update($criteria, $data, $options);
    }

    public function count($collection, $query = [])
    {
        $this->connect();
        return $this->db->{$collection}->count($query);
    }

    public function remove($collection, $criteria = [], $options = [])
    {
        $this->connect();
        return $this->db->{$collection}->remove($criteria, $options);
    }

    public function drop($collection)
    {
        $this->connect();
        return $this->db->{$collection}->drop();
    }

    public function find($collection, $query = [], $fields = [])
    {
        $this->connect();
        return $this->db->{$collection}->find($query, $fields);
    }

    public function findOne($collection, $query = [], $fields = [])
    {
        $this->connect();
        return $this->db->{$collection}->findOne($query, $fields);
    }

    public function findAndModify($collection, $query = [], $update = [], $fields = [], $options = [])
    {
        $this->connect();
        return $this->db->{$collection}->findAndModify($query, $update, $fields, $options);
    }
}