<?php

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
