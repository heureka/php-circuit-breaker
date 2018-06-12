<?php

namespace CircuitBreaker;


interface EventListenerInterface
{

    /**
     * @param array  $completeStatus Contains keys:
     *      'status'                => string see CircuitBreaker\Status
     *      'attemptCounter'        => int
     *      'failureStartTimestamp' => int
     *
     * @return void
     */
    public function changeHandle($completeStatus);

}
