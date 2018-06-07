<?php

namespace CircuitBreaker\Storage;


class ApcCacheStorageAdapter implements StorageAdapterInterface
{

    /**
     * @var string
     */
    private $keyPrefix;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param string $keyPrefix
     * @param int    $ttl       in seconds
     *
     * @throws StorageException
     */
    public function __construct($keyPrefix, $ttl)
    {
        $this->checkApcCacheExistence();

        $this->keyPrefix = $keyPrefix;
        $this->ttl = $ttl;
    }

    /**
     * Checks if the extension exists.
     *
     * @throws StorageException
     */
    protected function checkApcCacheExistence() {
        if (!function_exists("apc_store")) {
            throw new StorageException("APC extension not loaded.");
        }
    }

    /**
     * @param string $key
     *
     * @return string
     *
     * @throws StorageException
     */
    public function load($key)
    {
        $data = apc_fetch($this->keyPrefix . $key);
        if ($data === false) {
            throw new StorageException("Can not load data from APC Cache. Key: $key");
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     * @throws StorageException
     */
    public function save($key, $value)
    {
        $result = apc_store($this->keyPrefix . $key, $value, $this->ttl);
        if ($result === false) {
            throw new StorageException("Can not save data to APC Cache. Key: $key");
        }
    }

}
