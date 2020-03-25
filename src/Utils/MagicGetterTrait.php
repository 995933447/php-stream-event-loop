<?php
namespace Bobby\StreamEventLoop\Utils;

trait MagicGetterTrait
{
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        throw new \Exception(get_class($this) ."::$property does not exist.");
    }
}