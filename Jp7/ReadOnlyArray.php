<?php
namespace Jp7;

class ReadOnlyArray implements \Iterator, \ArrayAccess
{
    protected $index = 0;

    protected $storage = array();

    public function __construct(array $array)
    {
        $this->storage = $array;
        reset($this->storage);
        $this->index = key($this->storage);
    }

    public function rewind()
    {
        reset($this->storage);
    }

    public function current()
    {
        return $this->storage[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        next($this->storage);
        $this->index = key($this->storage);
    }

    public function valid()
    {
        return isset($this->storage[$this->index]);
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('You cannot write values to a Read Only Array after it is created.');
    }

    public function offsetExists($offset)
    {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('You cannot delete values from a Read Only Array after it is created.');
    }

    public function offsetGet($offset)
    {
        if (isset($this->storage[$offset])) {
            return $this->storage[$offset];
        } else {
            throw new \Exception("$offset does not exist");
        }
    }
}
