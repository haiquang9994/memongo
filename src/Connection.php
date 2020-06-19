<?php

namespace MeMongo;

use MeMongo\Exception\EmptyDatabaseNameException;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

class Connection
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Database
     */
    protected $database;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    protected function connect()
    {
        $config = $this->config;
        $auth = '';
        if (isset($config['username'])) {
            $auth = sprintf("%s:%s@", $config['username'], $config['password'] ?? '');
        }
        $dbname = $config['dbname'] ?? '';
        if (!$dbname) {
            throw new EmptyDatabaseNameException("DB Name not found");
        }
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '27017';
        $uri = sprintf("mongodb://%s%s:%s", $auth, $host, $port);
        $this->client = new Client($uri);
        $this->database = $this->client->{$dbname};
    }

    public function getDbCollection(string $collectionName): Collection
    {
        return $this->database->{$collectionName};
    }
}
