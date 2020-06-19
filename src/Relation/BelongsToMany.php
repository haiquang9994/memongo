<?php

namespace MeMongo\Relation;

use MeMongo\Model\Item;
use MeMongo\Manager;

class BelongsToMany extends Relation
{
    protected $collection;

    protected $foreignPivotKey;

    protected $relatedPivotKey;

    protected $parentKey;

    protected $relatedKey;

    public function __construct(string $related, string $collection, string $foreignPivotKey, string $relatedPivotKey, string $parentKey, string $relatedKey)
    {
        parent::__construct($related);
        $this->collection = $collection;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
    }

    public function getData(Item $item)
    {
        $manager = Manager::instance();
        $collection = $manager->getCollection($this->related);
        $value = $item->{$this->parentKey};
        $relatedIds = $manager->collection($this->collection)->filterEqual($this->foreignPivotKey, $value)->pluck($this->relatedPivotKey)->all();
        return $collection->query()->filterIn($this->relatedKey, $relatedIds)->get();
    }
}
