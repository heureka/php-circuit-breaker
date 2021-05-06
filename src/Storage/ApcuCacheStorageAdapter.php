<?php
/**
 * Copyright (c) 2021 Heureka Group a.s. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 *limitations under the License.
 */
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
        $result = apcu_store($this->keyPrefix . $key, $value, $this->ttl);
        if ($result === false) {
            throw new StorageException("Can not save data to APCu Cache. Key: $key");
        }
    }

}
