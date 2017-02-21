<?php

namespace Swaggest\JsonSchema;

class MagicMap implements \ArrayAccess, \JsonSerializable
{
    protected $__arrayOfData = array();

    public function __set($name, $column)
    {
        $this->__arrayOfData[$name] = $column;
        return $this;
    }

    public function &__get($name)
    {
        if (isset($this->__arrayOfData[$name])) {
            return $this->__arrayOfData[$name];
        } else {
            $tmp = null;
            return $tmp;
        }
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->__arrayOfData);
    }

    public function &offsetGet($offset)
    {
        if (isset($this->__arrayOfData[$offset])) {
            return $this->__arrayOfData[$offset];
        } else {
            $tmp = null;
            return $tmp;
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->__arrayOfData[$offset]);
    }

    public function &toArray()
    {
        return $this->__arrayOfData;
    }

    public function jsonSerialize()
    {
        return (object)$this->__arrayOfData;
    }

    private $__propertyToDataMap;
    public function setPropertyToDataMap($map)
    {
        $this->__propertyToDataMap = $map;
        return $this;
    }

    public function getPropertyToDataMap()
    {
        return $this->__propertyToDataMap;
    }

    private $__dataToPropertyMap;

    /**
     * @return mixed
     */
    public function getDataToPropertyMap()
    {
        return $this->__dataToPropertyMap;
    }

    /**
     * @param mixed $_dataToPropertyMap
     * @return MagicMap
     */
    public function setDataToPropertyMap($_dataToPropertyMap)
    {
        $this->__dataToPropertyMap = $_dataToPropertyMap;
        return $this;
    }

}