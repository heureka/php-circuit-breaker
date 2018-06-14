PHP Circuit Breaker
===================================================


What is Circuit Breaker
----------

[Wikipedia.org](https://en.wikipedia.org/wiki/Circuit_breaker_design_pattern)

Why use this solution of CB in PHP
----------

There had been several concurrent solutions of Circuit Breaker for PHP on Github but there is no Circuit Breaker for dynamic calling of callback with own responsibility for handling failures. 
This library was developed especially for easy of use that developer whose uses this library does not have to take interest how it works inside.

How it works
----------

Generally The library is for handling failures of the call of the callback, which may be e.g. call of another service.

Calls the callback and if it fails it tries in certain seconds or in certain attempts which is set in constructor.

Contains only two states OPEN (if the callback raises an exception) or CLOSE (if the callback returns data).  

Dependencies
----------

- PHP v7.0.0^ (It may work in lower version but it has not been tested yet.)

How to install
----------

1. Add the lines below to your composer.json file

```json
"repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:heureka/php-circuit-breaker.git"
    },
  ],
  "require": {
    "heureka/php-circuit-breaker": "*"
  }
]
```

2. Run this command

```bash
$ composer install
```

Example of usage
----------

```php
$stopAttemptTime = 10; // How long it should wait for a next try. In seconds.
$stopAttemptCount = 200; // How many attempts it should wait for a next try.

$dataStorage = new \CircuitBreaker\Storage\DataStorage(
    new \CircuitBreaker\Storage\ApcuCacheStorageAdapter(), // APC extension must be installed. 
    'serviceName' // Just for definition of key in the data storage (APC cache key).
);

$circuitBreaker = new \CircuitBreaker\CircuitBreaker($dataStorage, $stopAttemptTime, $stopAttemptCount);
$circuitBreaker->call(
    function() use($externalService) {
        $externalService->callMethod();
    }
);
```
