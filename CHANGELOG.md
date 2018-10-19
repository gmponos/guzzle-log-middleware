# Changelog

All Notable changes to `gmponos/guzzle_logger` will be documented in this file

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
