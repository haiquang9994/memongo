<?php

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if (!function_exists('ObjectId')) {
    function ObjectId($id): ObjectId
    {
        if ($id instanceof ObjectId) {
            return $id;
        }
        return new ObjectId($id);
    }
}

if (!function_exists('TryToDate')) {
    function TryToDate($data): ?DateTime
    {
        if ($data instanceof DateTime) {
            return $data;
        }
        if (is_numeric($data)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', $data / 1000));
            return $date instanceof DateTime ? $date : null;
        }
        if (is_string($data)) {
            $formats = ['Y-m-d H:i:s', 'Y-m-d', 'H:i:s'];
            $date = null;
            while (!$date && count($formats) > 0) {
                $date = DateTime::createFromFormat(array_shift($formats), $data);
            }
            return $date instanceof DateTime ? $date : null;
        }
        return null;
    }
}


if (!function_exists('UTCDateTime')) {
    function UTCDateTime($data): UTCDateTime
    {
        $date = TryToDate($data);
        if ($date instanceof DateTime) {
            return new UTCDateTime($date);
        }
        return null;
    }
}
