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
namespace CircuitBreaker;

use CircuitBreaker\Storage\DataStorage;


class CircuitBreaker
{

    /**
     * @var Storage\DataStorage
     */
    private $dataStorage;

    /**
     * @var array[EventListenerInterface]
     */
    private $eventListeners = [];

    /**
     * @var int How long it should wait for a next try. In seconds.
     */
    private $stopAttemptTime;

    /**
     * @var int How many attempts it should wait for a next try.
     */
    private $stopAttemptCount;

    /**
     * @var int How many force retries immediately after the services has failed
     */
    private $failureImmediateRetries;

    /**
     * @var \Exception
     */
    private $lastException;

    /**
     * @param DataStorage $dataStorage
     * @param int         $stopAttemptTime         How long it should wait for a next try. In seconds.
     * @param int         $stopAttemptCount        How many attempts it should wait for a next try.
     * @param int         $failureImmediateRetries How many force retries immediately after the services has failed.
     */
    public function __construct(
        DataStorage $dataStorage,
        $stopAttemptTime,
        $stopAttemptCount,
        $failureImmediateRetries = 0
    ) {
        $this->dataStorage = $dataStorage;
        $this->stopAttemptTime = $stopAttemptTime;
        $this->stopAttemptCount = $stopAttemptCount;
        $this->failureImmediateRetries = $failureImmediateRetries;
    }

    /**
     * @param EventListenerInterface $eventListener
     */
    public function addEventListener(EventListenerInterface $eventListener)
    {
        $this->eventListeners[] = $eventListener;
    }

    /**
     * @param callable $callback
     *
     * @return mixed Service call return value
     *
     * @throws CircuitBreakerOpenException If the circuit breaker is currently OPEN.
     * @throws \Exception An exception from the service call.
     */
    public function call($callback)
    {
        if (!$this->isAvailable()) {
            $this->dataStorage->incrementFailureAttempt();

            throw new CircuitBreakerOpenException(
                'Circuit Breaker is OPEN, that means it can not call the service.',
                0,
                $this->lastException
            );
        }

        try {
            $returnValue = $this->callCallback($callback);
            if ($this->dataStorage->getStatus() !== Status::CLOSED) {
                $this->dataStorage->reportCloseStatus();

                if ($this->eventListeners) {
                    foreach ($this->eventListeners as $eventListener) {
                        $eventListener->changeHandle($this->dataStorage->toArray());
                    }
                }
            }

            return $returnValue;
        } catch (\Exception $e) {
            $this->dataStorage->reportOpenStatus();
            $this->lastException = $e;

            if ($this->eventListeners) {
                foreach ($this->eventListeners as $eventListener) {
                    $eventListener->changeHandle($this->dataStorage->toArray());
                }
            }

            throw $e;
        }
    }

    /**
     * @param callable $callback
     *
     * @return mixed Service call return value
     *
     * @throws \Exception
     */
    private function callCallback($callback)
    {
        $numberOfImmediateRetries = 0;
        while (true) {
            try {
                return call_user_func($callback);
            } catch (\Exception $e) {
                $numberOfImmediateRetries++;

                if ($numberOfImmediateRetries >= $this->failureImmediateRetries) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @return bool True if the circuit breaker is CLOSE or the service is already ready to call otherwise false.
     */
    private function isAvailable()
    {
        // current status is CLOSE
        if ($this->dataStorage->getStatus() === Status::CLOSED) {

            return true;
        }

        // Blocking time is up
        if ((time() - $this->dataStorage->getFailureStartTimestamp()) > $this->stopAttemptTime) {

            return true;
        }

        // If number of attempts to wait for a next request exceeded a set limit.
        if ($this->dataStorage->getAttemptCounter() > $this->stopAttemptCount) {

            return true;
        }

        return false;
    }

}
