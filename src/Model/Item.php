<?php

namespace MeMongo\Model;

use DateTime;
use DateTimeZone;
use MeMongo\Collection;
use MeMongo\Relation\BelongsTo;
use MeMongo\Relation\BelongsToMany;
use MeMongo\Relation\HasMany;
use MeMongo\Relation\HasOne;
use MeMongo\Relation\Relation;
use MongoDB\Model\BSONDocument;
use ReflectionClass;

class Item extends BSONDocument
{
    protected static $collectionName;

    protected static $collections = [];

    protected static $timestamps = true;

    protected $fillable = [];
    
    protected $original;

    protected $relations = [];

    public static function setCollection($collection)
    {
        static::$collections[static::class] = $collection;
    }

    public static function getCollectionName(): ?string
    {
        return static::$collectionName;
    }

    public static function hasTimestamps()
    {
        return static::$timestamps;
    }

    protected function getCollection(): Collection
    {
        return static::$collections[static::class];
    }

    public function clone()
    {
        return clone $this;
    }

    public function save(): bool
    {
        $changed = $this->getChanged();
        if ($changed) {
            $changed['updated_at'] = UTCDateTime(new DateTime());
            if ($this->getCollection()->update($this->_id, $changed)) {
                $this->original = array_merge($this->original, $changed);
                return true;
            }
        }
        return false;
    }

    public function delete(): bool
    {
        if ($id = $this->getId()) {
            return $this->getCollection()->delete($id);
        }
        return false;
    }

    public function getChanged()
    {
        $changes = [];
        foreach ($this as $key => $value) {
            if ($key !== '_id' && (!array_key_exists($key, $this->original) || $value !== $this->original[$key])) {
                $changes[$key] = $value;
            }
        }
        return $changes;
    }

    public function getId()
    {
        return strval($this->_id);
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    public function bsonUnserialize(array $data)
    {
        parent::__construct($data);
        $this->original = $data;
        unset($this->original['_id']);
    }

    protected function isTimestampKey(string $key)
    {
        return in_array($key, ['updated_at', 'created_at']);
    }

    protected function nestedGet(string $key)
    {
        $value = null;
        if (method_exists($this, $key)) {
            $reflection = new ReflectionClass($this);
            $nestedMethod = $reflection->getMethod($key);
            $value = $nestedMethod->invoke($this);
            if ($value instanceof Relation) {
                if (!isset($this->relations[$key])) {
                    $this->relations[$key] = $value->getData($this);
                }
                return $this->relations[$key];
            }
            return $nestedMethod->invoke($this);
        }
        return $value;
    }

    public function offsetGet($key)
    {
        if (isset($this[$key])) {
            $value = parent::offsetGet($key);
        } else {
            $value = $this->nestedGet($key);
        }
        if ($this->isTimestampKey($key)) {
            $value = $value->toDateTime()->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }
        return $value;
    }

    public function offsetSet($key, $value)
    {
        if ($this->isTimestampKey($key)) {
            $value = UTCDateTime($value);
        }
        parent::offsetSet($key, $value);
    }

    protected function hasOne(string $related, string $foreignKey, string $localKey)
    {
        return new HasOne($related, $foreignKey, $localKey);
    }

    protected function hasMany(string $related, string $foreignKey, string $localKey)
    {
        return new HasMany($related, $foreignKey, $localKey);
    }

    protected function belongsTo(string $related, string $foreignKey, string $ownerKey)
    {
        return new BelongsTo($related, $foreignKey, $ownerKey);
    }

    protected function belongsToMany(string $related, string $collection, string $foreignPivotKey, string $relatedPivotKey, string $parentKey = '_id', string $relatedKey = '_id')
    {
        return new BelongsToMany($related, $collection, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey);
    }
}
