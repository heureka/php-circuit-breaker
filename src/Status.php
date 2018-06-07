<?php

namespace CircuitBreaker;


class Status
{

    /**
     * If a service can not accept a new requests
     */
    const OPEN = 'open';

    /**
     * If a service can accept a new requests
     */
    const CLOSED = 'closed';

}
