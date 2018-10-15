
[![codecov](https://codecov.io/gh/gmponos/guzzle-log-middleware/branch/master/graph/badge.svg)](https://codecov.io/gh/gmponos/guzzle-log-middleware)
[![Build Status](https://travis-ci.org/gmponos/guzzle-log-middleware.svg?branch=master)](https://travis-ci.org/gmponos/guzzle-log-middleware)
[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/gmponos/monolog-slack/blob/master/LICENSE.md)

# Guzzle Logger Middleware

This is a middleware for [guzzle](https://github.com/guzzle/guzzle) that will help you automatically log every request 
and response using a PSR-3 logger.

The middleware is functional with Guzzle 6.

**Important note**: This package is still in version 0.x.x. According to [semantic versioning](https://semver.org/) major changes can occur while
we are still on 0.x.x version. If you use the package for a project that is in production please lock this package in your composer
to a specific version like `^0.3.0`.

**Important note-2**: Next version will drop support for PHP5.

## Install

Via Composer

``` bash
$ composer require gmponos/guzzle_logger
```

## Usage

### Simple usage

``` php
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LoggerMiddleware($logger));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

From now on each request and response you make that you receive using the ``$client`` will be logged.
The default levels that the middleware uses for logging are the following.

- Requests are logged with level DEBUG.
- Request statistics are logged with level DEBUG.
- Responses with http status code < 400 with level DEBUG.
- Responses with http status code > 400 & < 500 with level ERROR.
- Responses with http status code >= 500 with level CRITICAL.
- If no status code or response is returned the log level is CRITICAL.

### Advanced initialization

The signature of the LoggerMiddleware class is the following:

``LoggerMiddleware(LoggerInterface $logger, HandlerInterface $handler = null, $onExceptionsOnly = false, $logStatistics = false)``

- **logger** - The PSR-3 logger to use for logging.
- **handler** - A HandlerInterface class that will be responsible for logging your request/response. Check Handlers sections.
- **onExceptionOnly** - By default the middleware is set to log every request and response. If you wish that to log 
the requests and responses only when guzzle returns a rejection set this as true. Guzzle returns a rejection when 
`http_errors` option is set to true, meaning that it will throw exception in cases a 4xx or 5xx response is received. 
- **logStatistics** - If you set logStatistics as true and this as true then guzzle will also log statistics about the requests.

### Handlers

In order to make the middleware more flexible we allow the developers to initialize the middleware and pass a handler 
during the construction. This handler must implement a `HandlerInterface` and it will be responsible for logging. 

So now let's say that we have the following handler.

``` php
<?php

namespace Gmponos\GuzzleLogger\Handler;

use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerInterface;

final class SimpleHandler implements HandlerInterface
{
    public function log(LoggerInterface $logger, $value, array $options = [])
    {
        if ($value instanceof MessageInterface) {
            $logger->debug('Guzzle HTTP message' . \GuzzleHttp\Psr7\str($value));
        }

        return;
    }
}
```

We can pass the handler above during construction of the middleware.

```
use Gmponos\GuzzleLogger\Middleware\LoggerMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LoggerMiddleware($logger, new SimpleHandler()));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

If no handler is passed the middleware will initialize it's own handler. At the moment the default one is `ArrayHandler`

### Using options on each request

You can set on each request options about your log.

```php
$client->get("/", [
    'log' => [
        'on_exception_only' => true,
        'statistics' => true,
        'error_threshold' => null,
        'warning_threshold' => null,
        'sensitive' => true,
        'levels' => [
            400 => 'info'
            401 => 'warning'
            ...
        ]
    ]
]);
```

- ``sensitive`` if you set this to true then the body of request/response will not be logged as it will be considered that it contains sensitive information.
- ``on_exception_only`` Do not log anything unless if the response status code is above the threshold.
- ``statistics`` if the `on_exception_only` option/variable is true and this is also true the middleware will log statistics about the HTTP call.
- ``levels`` set custom log levels for each response status code

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Credits

- [George Mponos](gmponos@gmail.com)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Todo
 - Create more handlers to log request/responses
 - More tests
