# Guzzle Log Middleware

[![codecov](https://codecov.io/gh/gmponos/guzzle-log-middleware/branch/master/graph/badge.svg)](https://codecov.io/gh/gmponos/guzzle-log-middleware)
[![Total Downloads](https://img.shields.io/packagist/dt/gmponos/guzzle_logger.svg)](https://packagist.org/packages/gmponos/guzzle_logger)
[![Build Status](https://travis-ci.org/gmponos/guzzle-log-middleware.svg?branch=master)](https://travis-ci.org/gmponos/guzzle-log-middleware)
[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/gmponos/monolog-slack/blob/master/LICENSE.md)
[![PHPPackages Rank](http://phppackages.org/p/gmponos/guzzle_logger/badge/rank.svg)](http://phppackages.org/p/gmponos/guzzle_logger)

This is a middleware for [guzzle](https://github.com/guzzle/guzzle) that will help you automatically log every request 
and response using a PSR-3 logger.

The middleware is functional with Guzzle 6.

### Important Notes
- This package is still in version 0.x.x. According to [semantic versioning](https://semver.org/) major changes can occur while
we are still on 0.x.x version. If you use the package for a project that is in production please lock this package in your composer
to a specific version like `^0.3.0`.

- Hopefully 0.8.0 version will be the last unstable. Please share your feedback or star the project if you like it.

## Install

Via Composer

``` bash
$ composer require gmponos/guzzle_logger
```

## Usage

### Simple usage

```php
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LogMiddleware($logger));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

From now on each request and response you execute using `$client` object will be logged.
By default the middleware logs every activity with level `DEBUG`.

### Advanced initialization

The signature of the `LogMiddleware` class is the following:

```php
\GuzzleLogMiddleware\LogMiddleware(
    \Psr\Log\LoggerInterface $logger, 
    \GuzzleLogMiddleware\Handler\HandlerInterface $handler = null, 
    bool $onFailureOnly = false, 
    bool $logStatistics = false
);
```

- **logger** - The PSR-3 logger to use for logging.
- **handler** - A HandlerInterface class that will be responsible for logging your request/response. Check Handlers sections.
- **onFailureOnly** - By default the middleware is set to log every request and response. If you wish to log 
the requests and responses only when guzzle returns a rejection set this as true or when an exception occurred. 
Guzzle returns a rejection when (http_errors)[http://docs.guzzlephp.org/en/stable/request-options.html#http-errors] option is set to true. 
- **logStatistics** - If you this option as true then the middleware will also log statistics about the requests.

### Handlers

In order to make the middleware more flexible we allow the developer to initialize it passing a handler. 
A handler must implement a `HandlerInterface` and it will be responsible for logging. 

Let's say that we create the following handler.

```php
<?php
namespace GuzzleLogMiddleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;

final class SimpleHandler implements HandlerInterface
{
    public function log(
        LoggerInterface $logger,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?\Exception $exception,
        ?TransferStats $stats,
        array $options
    ): void {
        $logger->debug('Guzzle HTTP request: ' . \GuzzleHttp\Psr7\str($request));
        return;
    }
}
```

We can pass the handler above during construction of the middleware.

```php
<?php
use GuzzleLogMiddleware\LogMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack stack with middlewares of guzzle already pushed inside of it.
$stack->push(new LogMiddleware($logger, new SimpleHandler()));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

If no handler is passed the middleware will initialize it's own handler. At the moment the default one is `MultiRecordArrayHandler`

#### MultiRecordArrayHandler

#### StringHandler

### Log Level Strategies

A log level strategy can be used in order to define the level that the handler will use to log the Request/Response.

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
