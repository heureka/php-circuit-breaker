<?php

namespace CircuitBreaker\Storage;


class DummyStorageAdapter implements StorageAdapterInterface
{

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function load($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function save($key, $value)
    {
        $this->data[$key] = $value;
    }

}
