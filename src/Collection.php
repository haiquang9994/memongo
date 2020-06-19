<?php

namespace MeMongo;

use MeMongo\Model\Item;
use MeMongo\Query\Builder;

class Collection
{
    protected $modelName;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, string $modelName)
    {
        $this->connection = $connection;
        $this->modelName = $modelName;
        $modelName::setCollection($this);
    }

    public function query()
    {
        return new Builder($this->connection, $this->modelName);
    }

    public function get()
    {
        return $this->query()->get();
    }

    public function first()
    {
        return $this->query()->first();
    }

    public function find($id)
    {
        return $this->query()->find($id);
    }

    public function insert(array $attributes): Item
    {
        return $this->query()->insert($attributes);
    }

    public function insertMany(array $attributeList): array
    {
        return $this->query()->insertMany($attributeList);
    }

    public function update($id, array $changes): bool
    {
        return $this->query()->update($id, $changes);
    }

    public function delete($id): bool
    {
        return $this->query()->delete($id);
    }

    public function addIndex(string $name, array $fields, array $options = [])
    {
        return $this->query()->addIndex($name, $fields, $options);
    }

    public function dropIndex(string $name)
    {
        return $this->query()->dropIndex($name);
    }
}
