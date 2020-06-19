<?php

namespace MeMongo\Relation;

use MeMongo\Model\Item;
use MeMongo\Manager;

class HasOne extends Relation
{
    protected $foreignKey;

    protected $localKey;

    public function __construct(string $related, string $foreignKey, string $localKey)
    {
        parent::__construct($related);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getData(Item $item)
    {
        $manager = Manager::instance();
        $collection = $manager->getCollection($this->related);
        $value = $item->{$this->localKey};
        return $collection->query()->filterEqual($this->foreignKey, $value)->first();
    }
}
