<?php

namespace MeMongo;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use MeMongo\Model\Item;

class Items implements IteratorAggregate, ArrayAccess
{
    /**
     * @var Item[]
     */
    protected $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all()
    {
        return $this->items;
    }

    public function clone()
    {
        return new Items($this->cloneItems());
    }

    public function cloneItems(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->clone();
        }
        return $items;
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $item) {
            call_user_func($callback, $item);
        }
        return $this;
    }

    public function map(callable $callback)
    {
        return array_map($callback, $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function pluck(string $value, string $key = null): Items
    {
        $results = [];
        foreach ($this->items as $item) {
            $itemValue = $item->{$value};
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = $item->{$key};
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = strval($itemKey);
                }
                $results[$itemKey] = $itemValue;
            }
        }
        return new Items($results);
    }

    public function first(): ?Item
    {
        return $this->items[0] ?? null;
    }

    public function last(): ?Item
    {
        return end($this->items);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}
