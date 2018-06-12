<?php

namespace CircuitBreaker\Storage;


interface StorageAdapterInterface
{

    /**
     * @param string $key
     *
     * @return string
     *
     * @throws StorageException
     */
    public function load($key);

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     *
     * @throws StorageException
     */
    public function save($key, $value);

}
