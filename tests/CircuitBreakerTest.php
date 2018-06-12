<?php

namespace CircuitBreaker;

use CircuitBreaker\Storage\DataStorage;
use CircuitBreaker\Storage\DummyStorageAdapter;
use PHPUnit\Framework\TestCase;


class CircuitBreakerTest extends TestCase
{

    public function testStatusClose()
    {
        $returnValue = 'Service call return value';
        $serviceCall = function() use($returnValue) {return $returnValue;};

        $dataStorage = new DataStorage(new DummyStorageAdapter(), 'serviceName');
        $circuitBreaker = new CircuitBreaker($dataStorage, 60, 5);

        $mockEventListener = $this->getMockBuilder(EventListenerInterface::class)->setMethods(['changeHandle'])->getMock();
        $mockEventListener->expects($this->never())->method('changeHandle');
        $circuitBreaker->addEventListener($mockEventListener);

        $this->assertSame($returnValue, $circuitBreaker->call($serviceCall));
        $this->assertSame($returnValue, $circuitBreaker->call($serviceCall));
        $this->assertSame($returnValue, $circuitBreaker->call($serviceCall));
    }

    public function testAttemptCountUp()
    {
        $returnValue = 'Service call return value';
        $serviceCall = function() use($returnValue) {return $returnValue;};
        $failingServiceCall = function() {throw new \Exception('testException');};

        $dataStorage = new DataStorage(new DummyStorageAdapter(), 'serviceName');
        $circuitBreaker = new CircuitBreaker($dataStorage, 60, 3);

        $mockEventListener = $this->getMockBuilder(EventListenerInterface::class)->setMethods(['changeHandle'])->getMock();
        $mockEventListener->expects($this->exactly(2))->method('changeHandle');
        $circuitBreaker->addEventListener($mockEventListener);

        try {
            $circuitBreaker->call($failingServiceCall);
        } catch (\Exception $e) {
            $this->assertSame('testException', $e->getMessage());
        }

        $this->checkOpenCircuitBreaker($circuitBreaker, $serviceCall);
        $this->checkOpenCircuitBreaker($circuitBreaker, $serviceCall);
        $this->checkOpenCircuitBreaker($circuitBreaker, $serviceCall);

        $this->assertSame($returnValue, $circuitBreaker->call($serviceCall));
    }

    public function testAttemptTimeUp()
    {
        $returnValue = 'Service call return value';
        $serviceCall = function() use($returnValue) {return $returnValue;};
        $failingServiceCall = function() {throw new \Exception('testException');};

        $dataStorage = new DataStorage(new DummyStorageAdapter(), 'serviceName');
        $circuitBreaker = new CircuitBreaker($dataStorage, 1, 5);

        $mockEventListener = $this->getMockBuilder(EventListenerInterface::class)->setMethods(['changeHandle'])->getMock();
        $mockEventListener->expects($this->exactly(2))->method('changeHandle');
        $circuitBreaker->addEventListener($mockEventListener);

        try {
            $circuitBreaker->call($failingServiceCall);
        } catch (\Exception $e) {
            $this->assertSame('testException', $e->getMessage());
        }

        $this->checkOpenCircuitBreaker($circuitBreaker, $serviceCall);
        $this->checkOpenCircuitBreaker($circuitBreaker, $serviceCall);
        sleep(2);

        $this->assertSame($returnValue, $circuitBreaker->call($serviceCall));
    }

    public function testImmediateAttempts()
    {
        $dataStorage = new DataStorage(new DummyStorageAdapter(), 'serviceName');
        $circuitBreaker = new CircuitBreaker($dataStorage, 1, 5, 3);

        $mock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['incrementRetries'])
            ->getMock();
        $mock->expects($this->exactly(3))->method('incrementRetries')->willThrowException(new \Exception('testException'));

        try {
            $circuitBreaker->call(function() use ($mock) {
                $mock->incrementRetries();
            });
        } catch (\Exception $e) {
            $this->assertSame('testException', $e->getMessage());
        }
    }

    private function checkOpenCircuitBreaker(CircuitBreaker $circuitBreaker, $callback)
    {
        try {
            $circuitBreaker->call($callback);

            $this->fail('It should have failed on CircuitBreakerOpenException.');
        } catch (CircuitBreakerOpenException $e) {}
    }

}
