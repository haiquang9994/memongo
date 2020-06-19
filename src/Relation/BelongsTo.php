<?php

namespace MeMongo\Relation;

use MeMongo\Model\Item;
use MeMongo\Manager;

class BelongsTo extends Relation
{
    protected $foreignKey;

    protected $ownerKey;

    public function __construct(string $related, string $foreignKey, string $ownerKey)
    {
        parent::__construct($related);
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    public function getData(Item $item)
    {
        $manager = Manager::instance();
        $collection = $manager->getCollection($this->related);
        $value = $item->{$this->foreignKey};
        return $collection->query()->filterEqual($this->ownerKey, $value)->first();
    }
}
