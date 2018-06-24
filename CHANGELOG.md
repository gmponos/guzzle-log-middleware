# Changelog

All Notable changes to `Gmponos\GuzzleHttpLogger` will be documented in this file

## 0.2.0 - 2018-06-24

### Added
- A new option is added with key `sensitive`. When you make a request using guzzle you can set this option to true
in order not to log the body of the request and response. Example:

```
$guzzle->get('/test', ['log' => ['sensitive' => true]]); 
```

### Changed
- For performance reasons when a body of a request/response is bigger than 3500 characters the body is truncated.
Before a key with `summary` was added in the logs and also a key with `body`. The `body` contained the wording 
"Body was truncated because of it's size". This is changed now and the `summary` key is removed therefore the `body` 
key will contain the summary from now on.
- If a Stream is not seekable or readable the body can not be logged. A wording is added instead in the key `body`.  

## 0.1.0 - 2018-06-19

### Added
- Created the first functionality of the middleware
