# Changelog

All Notable changes to `gmponos/guzzle_logger` will be documented in this file

## 2.2.0 - 2021-04-04

## Changes
- Allow psr/log in version 2 and 3, like guzzlehttp/guzzle do it ([44](https://github.com/gmponos/guzzle-log-middleware/pull/44))

## 2.1.0 - 2021-03-18

That was a long layoff

## Changes
- Allow PHP 8
- Fix a deprecation on MultiRecordArrayHandler regarding query

## 2.0.0 - 2020-07-05

### Changed
- [BC] Changed the signature of `HandlerInterface::log` to allow Throwables. Now the signature is
```php
HandlerInterface::log(
    LoggerInterface $logger,
    RequestInterface $request,
    ?ResponseInterface $response = null,
    ?Throwable $exception = null,
    ?TransferStats $stats = null,
    array $options = []
)
```

- Allow guzzle 7

## 1.1.0 - 2019-09-03

### Added
- Added parameters in MultiRecordArrayHandler in order to customize the size truncated [#25](https://github.com/gmponos/guzzle-log-middleware/pull/25)
- MultiRecordArrayHandler will parse form requests as and log them as array [#27](https://github.com/gmponos/guzzle-log-middleware/pull/27)  

## 1.0.1 - 2019-06-18

### Changes
- Fixes [#24](https://github.com/gmponos/guzzle-log-middleware/issues/24). Body MUST rewind on huge responses.

## 1.0.0 - 2018-12-28

### Changes
- Added more classes of status codes to `ThresholdStrategy`

## 0.8.0 - 2018-12-13

### Changes

- Set as the default strategy in all handlers the `FixedStrategy`

**BREAKING CHANGES**
 
- `LogLevelStrategy` class is removed and it has been separated to smaller classes.
Check the `added` section below.
- Changed the namespaces completely. The new namespace is `GuzzleLogMiddleware` instead of `Gmponos\GuzzleLogger`.
Check the README file for instructions.
- Changed the signature of function `HandlerInterface::log`.
- Changed `ArrayHandler` to `MultiRecordArrayHandler`  

### Added
- `FixedStrategy` a strategy that you are able to set one level for all your logs.
- `ThresholdLevelStrategy` a strategy that works with thresholds depending on the status code. 
- `StatusCodeStrategy` a strategy that you are able to set a specific log level per status code.

## 0.7.0 - 2018-11-15

### Changed
- **BREAKING CHANGE** Dropped support for PHP 5 and require PHP 7.2 as minimum version.

## 0.6.0 - 2018-10-19

### Changed
- **BREAKING CHANGE** Changed the constructor function of middleware.
    - From now on you can pass a handler to it's constructor. Handlers are responsible for logging request/responses.
    - Removed threshold argument.

## 0.5.0 - 2018-10-01

### Changed
- **BREAKING CHANGE** Renamed the variable `$logRequestOnExceptionOnly` to `$onExceptionOnly`. The purpose of this constructor argument was 
to log request and responses only if an exception occurs. If you were manually setting this argument as true now you must set it
as false as the variables meaning is inverted.
- Deprecated the option `requests`. It will be removed on my next version.

## 0.4.0 - 2018-09-12

### Changed
- Removed `LoggerAwareTrait`. Therefore the logger can not be set after the construction of the middleware.
- Changed the variable name `$logRequests` of the constructor to `$logRequestOnExceptionOnly`.
- In case a message is not an `\Exception` or a `MessageInterface` an Exception is thrown.
- Changed all the functions except of `__construct` and `__invoke` to private. Same for the properties.

## 0.3.0 - 2018-08-23

### Changed
- The package was reading the headers of the Request/Response and if they contained `application/json` the body
was parsed into an array in order to be better readable in the logger. This has changed to match a regular expression
`/application\/[\w\.\+]*(json)/` in order to catch more cases. Thanks @eduarguzher [#4](https://github.com/gmponos/Guzzle-logger/pull/4)
- According to PHPStorm indication the `ext-json` needs to be installed in order for the package to work. Therefore
it was added as a requirement to `composer.json`

## 0.2.0 - 2018-06-24

### Added
- A new option is added with key `sensitive`. When you make a request using guzzle you can set this option to true
in order not to log the body of the request and response. Example:

```
$guzzle->get('/test', ['log' => ['sensitive' => true]]); 
```

### Changed
- Changed the required version of guzzle from `^6.3` to `6.*`. Package should be able to work with any version `6.*` of `guzzle`.  
- For performance reasons when a body of a request/response is bigger than 3500 characters the body is truncated.
When it was truncated a key with `summary` was added in the logs and also a key with `body`. The `body` contained only 
the wording "Body was truncated because of it's size". This is changed now and the `summary` key is removed and the `body` 
key will contain the summary from now on.
- If a Stream is not seekable or readable the body can not be logged. A wording is added instead in the key `body`.  

## 0.1.0 - 2018-06-19

### Added
- Created the first functionality of the middleware
