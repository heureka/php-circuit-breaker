<?php

namespace CircuitBreaker\Storage;


class ApcuCacheStorageAdapter implements StorageAdapterInterface
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
        $this->checkApcuCacheExistence();

        $this->keyPrefix = $keyPrefix;
        $this->ttl = $ttl;
    }

    /**
     * Checks if the extension exists.
     *
     * @throws StorageException
     */
    protected function checkApcuCacheExistence() {
        if (!function_exists("apcu_fetch")) {
            throw new StorageException("APCu extension not loaded.");
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function load($key)
    {
        return apcu_fetch($this->keyPrefix . $key);
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
        $result = apcu_add($this->keyPrefix . $key, $value, $this->ttl);
        if ($result === false) {
            throw new StorageException("Can not save data to APCu Cache. Key: $key");
        }
    }

}
