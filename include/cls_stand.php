<?php

class Stand implements ArrayAccess {
    protected $_data;
    protected $_identifier;
    protected $_terminal;
    protected $_priority;

    public function __construct(string $identifier, int $priority, $terminal = null) {
        $this->_identifier = $identifier;
        $this->_terminal = $terminal;
        $this->_priority = $priority;
    }

    public function offsetExists($offset): bool {
        return isset($this->_data[$offset]);
    }
  
    public function offsetGet($offset) {
        return $this->_data[$offset];
    }
  
    public function offsetSet($offset , $value) {
        $this->_data[$offset] = $value;
    }
  
    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    public function identifier(): string {
        return $this->_identifier;
    }

    public function terminal() {
        return $this->_terminal;
    }

    public function priority(): int {
        return $this->_priority;
    }

}