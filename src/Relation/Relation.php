<?php

namespace MeMongo\Relation;

use MeMongo\Model\Item;

abstract class Relation
{
    protected $related;

    public function __construct(string $related)
    {
        $this->related = $related;
    }

    abstract public function getData(Item $item);
}
