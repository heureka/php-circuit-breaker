<?php

namespace CircuitBreaker\Storage;

use PHPUnit\Framework\TestCase;
use CircuitBreaker\Status;


class DataStorageTest extends TestCase
{

    /**
     * @var StorageAdapterInterface
     */
    private $storageAdapter;

    public function setUp()
    {
        parent::setUp();
        if (function_exists("apc_store")) {
            $this->storageAdapter = new ApcCacheStorageAdapter('test', 60);
        } else {
            $this->storageAdapter = new DummyStorageAdapter();
        }
    }

    public function testLoadAndSave()
    {
        $serviceName = 'testServiceName';
        $failureStartTimestamp = time();

        $dataStorage = new DataStorage($this->storageAdapter, $serviceName);

        $dataStorage->reportOpenStatus();

        unset($dataStorage);
        $dataStorage = new DataStorage($this->storageAdapter, $serviceName);

        $this->checkStatus($dataStorage, Status::OPEN, 1, $failureStartTimestamp);

        $dataStorage->incrementFailureAttempt();
        $dataStorage->incrementFailureAttempt();

        unset($dataStorage);
        $dataStorage = new DataStorage($this->storageAdapter, $serviceName);

        $this->checkStatus($dataStorage, Status::OPEN, 3, $failureStartTimestamp);

        $dataStorage->reportCloseStatus();

        unset($dataStorage);
        $dataStorage = new DataStorage($this->storageAdapter, $serviceName);

        $this->checkStatus($dataStorage, Status::CLOSED, 0, 0);
    }

    /**
     * @param DataStorage $dataStorage
     * @param string      $status
     * @param int         $attemptCounter
     * @param int         $failureStartTimestamp
     */
    private function checkStatus(DataStorage $dataStorage, $status, $attemptCounter, $failureStartTimestamp)
    {
        $this->assertSame($status, $dataStorage->getStatus());
        $this->assertSame($attemptCounter, $dataStorage->getAttemptCounter());
        $this->assertSame($failureStartTimestamp, $dataStorage->getFailureStartTimestamp());
    }

}
