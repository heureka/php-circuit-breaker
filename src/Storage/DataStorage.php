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

use CircuitBreaker\Status;

/**
 * @author Vladimír Kašpar <vladimir.kaspar@heureka.cz>
 */
class DataStorage
{

    /**
     * @var StorageAdapterInterface
     */
    private $storageAdapter;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string see CircuitBreaker\Status
     */
    private $status = Status::CLOSED;

    /**
     * @var int
     */
    private $attemptCounter = 0;

    /**
     * @var int
     */
    private $failureStartTimestamp = 0;

    /**
     * @param StorageAdapterInterface $storageAdapter
     * @param string                  $serviceName
     */
    public function __construct(StorageAdapterInterface $storageAdapter, $serviceName)
    {
        $this->storageAdapter = $storageAdapter;
        $this->serviceName = $serviceName;

        $this->loadData();
    }

    /**
     * @return string see CircuitBreaker\Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getAttemptCounter()
    {
        return $this->attemptCounter;
    }

    /**
     * @return int
     */
    public function getFailureStartTimestamp()
    {
        return $this->failureStartTimestamp;
    }

    /**
     * Saves CLOSE status to the storage adapter.
     */
    public function reportCloseStatus()
    {
        $this->status = Status::CLOSED;
        $this->attemptCounter = 0;
        $this->failureStartTimestamp = 0;

        $this->saveData();
    }

    /**
     * Saves OPEN status to the storage adapter.
     */
    public function reportOpenStatus()
    {
        $this->status = Status::OPEN;
        $this->attemptCounter = 1;
        $this->failureStartTimestamp = time();

        $this->saveData();
    }

    /**
     * Increments failure attempt count and saves it to the storage adapter.
     */
    public function incrementFailureAttempt()
    {
        $this->attemptCounter++;

        $this->saveData();
    }

    /**
     * Loads data from the storage adapter.
     */
    private function loadData()
    {
        $data = $this->storageAdapter->load($this->serviceName);
        if ($data) {
            $data = json_decode($data, true);
            $this->status = $data['status'];
            $this->attemptCounter = $data['attemptCounter'];
            $this->failureStartTimestamp = $data['failureStartTimestamp'];
        }
    }

    /**
     * Saves data to the storage adapter.
     */
    private function saveData()
    {
        $this->storageAdapter->save($this->serviceName, json_encode($this->toArray()));
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'status' => $this->status,
            'attemptCounter' => $this->attemptCounter,
            'failureStartTimestamp' => $this->failureStartTimestamp,
        ];
    }

}
