<?php

namespace MeMongo;

use MeMongo\Model\Std;
use MeMongo\Exception\ConfigNotFoundException;

class Manager
{
    protected $defaultConfig = 'default';

    protected $configs = [];

    protected $connections = [];

    protected static $instance;

    public static function instance(): Manager
    {
        if (!static::$instance) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function setDetaultConfig(string $name)
    {
        $this->defaultConfig = $name;
        return $this;
    }

    public function setConfig(array $config, string $name = 'default')
    {
        $this->configs[$name] = $config;
        if (isset($this->connections[$name])) {
            unset($this->connections[$name]);
        }
    }

    protected function getConfig(string $name): array
    {
        if (!isset($this->configs[$name])) {
            throw new ConfigNotFoundException(sprintf("Not found config data for '%s'", $name));
        }
        return $this->configs[$name];
    }

    protected function getConnection(string $name): Connection
    {
        if (!isset($this->connections[$name])) {
            $config = $this->getConfig($name);
            $this->connections[$name] = new Connection($config);
        }
        return $this->connections[$name];
    }

    protected function getDefaultConnection()
    {
        return $this->getConnection($this->defaultConfig);
    }

    public function getCollection(string $modelName): Collection
    {
        return new Collection($this->getDefaultConnection(), $modelName);
    }

    public function collection(string $collectionName)
    {
        $collection = new Collection($this->getDefaultConnection(), Std::class);
        return $collection->query()->collection($collectionName);
    }
}
