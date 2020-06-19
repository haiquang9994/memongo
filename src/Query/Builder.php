<?php

namespace MeMongo\Query;

use DateTime;
use MeMongo\Connection;
use MeMongo\Model\Item;
use MeMongo\Model\Std;
use MeMongo\Exception\MeMongoException;
use MeMongo\Items;

class Builder
{
    protected $connection;

    protected $modelName;

    protected $filters = [];

    protected $sorts = [];

    protected $skip = 0;

    protected $limit = null;

    protected $collectionName = null;

    public function __construct(Connection $connection = null, string $modelName = null)
    {
        $this->connection = $connection;
        $this->modelName = $modelName;
    }

    public function get()
    {
        $items = $this->connection->getDbCollection($this->getCollectionName())->find($this->mergeFilter(), $this->mergeOptions());
        return new Items($items->toArray());
    }

    public function first(): ?Item
    {
        return $this->connection->getDbCollection($this->getCollectionName())->findOne($this->mergeFilter(), $this->mergeOptions());
    }

    public function insert(array $attributes)
    {
        if ($this->modelName::hasTimestamps()) {
            $attributes['created_at'] = UTCDateTime(new DateTime());
            $attributes['updated_at'] = UTCDateTime(new DateTime());
        }
        $insertResult = $this->connection->getDbCollection($this->getCollectionName())->insertOne($attributes);
        return $this->find($insertResult->getInsertedId());
    }

    public function insertMany(array $attributeList): array
    {
        $models = [];
        foreach ($attributeList as $attributes) {
            if (is_array($attributes)) {
                $models[] = $this->insert($attributes);
            }
        }
        return $models;
    }

    public function update($id, array $changes): bool
    {
        if ($this->modelName::hasTimestamps()) {
            $changes['updated_at'] = UTCDateTime(new DateTime());
        }
        $updateResult = $this->connection->getDbCollection($this->getCollectionName())->updateOne([
            '_id' => ObjectId($id),
        ], [
            '$set' => $changes,
        ]);
        return $updateResult->getMatchedCount() > 0;
    }

    public function delete($id)
    {
        $deleteResult = $this->connection->getDbCollection($this->getCollectionName())->deleteOne([
            '_id' => ObjectId($id),
        ]);
        return $deleteResult->getDeletedCount() > 0;
    }

    public function addIndex(string $name, array $fields, array $options = [])
    {
        $index_fields = [];
        foreach ($fields as $field) {
            $index_fields[$field] = 1;
        }
        $unique = boolval($options['unique'] ?? false);
        $this->connection->getDbCollection($this->getCollectionName())->createIndex($index_fields, ['unique' => $unique, 'name' => $name]);
        return $this;
    }

    public function dropIndex(string $name)
    {
        $this->connection->getDbCollection($this->getCollectionName())->dropIndex($name);
        return $this;
    }

    public function collection(string $collectionName)
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    protected function getCollectionName(): string
    {
        if ($this->modelName === Std::class) {
            if (!$this->collectionName) {
                throw new MeMongoException('Please select a collection');
            }
            return $this->collectionName;
        }
        return $this->modelName::getCollectionName();
    }

    public function find($id)
    {
        return $this->addFilter([
            '_id' => ObjectId($id),
        ])->first();
    }

    public function addFilter(array $filter)
    {
        if ($filter) {
            $this->filters[] = $filter;
        }
        return $this;
    }

    public function filterEqual(string $key, $value)
    {
        return $this->addFilter([
            $key => ['$eq' => $value],
        ]);
    }

    public function filterNotEqual(string $key, $value)
    {
        return $this->addFilter([
            $key => ['$ne' => $value],
        ]);
    }

    public function filterGreater(string $key, $value)
    {
        return $this->addFilter([
            $key => ['$gt' => $value],
        ]);
    }

    public function filterGreaterEqual(string $key, $value)
    {
        return $this->addFilter([
            $key => ['$gte' => $value],
        ]);
    }

    public function filterLess(string $key, $value)
    {
        return $this->addFilter([
            $key => ['$lt' => $value],
        ]);
    }

    public function filterLessEqual(string $key, $value)
    {
        return $this->addFilter([
            $key => ['$lte' => $value],
        ]);
    }

    public function filterIn(string $key, array $values)
    {
        return $this->addFilter([
            $key => ['$in' => array_values($values)],
        ]);
    }

    public function filterNotIn(string $key, array $values)
    {
        return $this->addFilter([
            $key => ['$nin' => array_values($values)],
        ]);
    }

    public function filterBetween(string $key, $firstValue, $secondValue)
    {
        return $this->addFilter([
            $key => ['$gte' => $firstValue, '$lte' => $secondValue],
        ]);
    }

    public function filterLike(string $key, string $regexValue)
    {
        return $this->addFilter([
            $key => ['$regex' => $regexValue],
        ]);
    }

    // function () { return (hex_md5(this.name) == "81dc9bdb52d04dc20036dbd8313ed055") } -> name = 1234
    // Available properties and functions in https://docs.mongodb.com/manual/reference/operator/query/where/#restrictions
    public function filterWhere(string $whereString)
    {
        return $this->addFilter([
            '$where' => "function () { $whereString }",
        ]);
    }

    public function filter(callable $callback, $operator = '$and')
    {
        $builder = new static;
        call_user_func($callback, $builder);
        $data = $builder->mergeFilter();
        if (isset($data['$and'])) {
            return $this->addFilter([
                $operator => $data['$and'],
            ]);
        }
        return $this->addFilter($data);
    }

    public function filterNot(callable $callback)
    {
        return $this->filter($callback, '$not');
    }

    public function filterNor(callable $callback)
    {
        return $this->filter($callback, '$nor');
    }

    public function filterOr(callable $callback)
    {
        return $this->filter($callback, '$or');
    }

    public function sort(string $key, string $type = 'desc')
    {
        $this->sorts[$key] = $type === 'desc' ? -1 : 1;
        return $this;
    }

    public function sortDesc(string $key)
    {
        return $this->sort($key, 'desc');
    }

    public function sortAsc(string $key)
    {
        return $this->sort($key, 'asc');
    }

    public function removeSorts()
    {
        $this->sorts = [];
        return $this;
    }

    public function removeFilters()
    {
        $this->filters = [];
        return $this;
    }

    public function skip(int $skip)
    {
        $this->skip = $skip;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function take(int $take)
    {
        return $this->limit($take);
    }

    public function pluck(string $value, string $key = null): Items
    {
        return $this->get()->pluck($value, $key);
    }

    protected function mergeFilter(): array
    {
        $data = [
            '$and' => [],
        ];
        foreach ($this->filters as $filter) {
            $data['$and'][] = $filter;
        }
        if (count($data['$and']) === 0) {
            return [];
        }
        return $data;
    }

    protected function mergeOptions(): array
    {
        $options = [
            'typeMap' => [
                'array' => 'array',
                'document' => $this->modelName,
                'root' => $this->modelName,
            ],
            'sort' => $this->sorts,
            'skip' => $this->skip,
        ];
        if ($this->limit) {
            $options['limit'] = $this->limit;
        }
        return $options;
    }
}
