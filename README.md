# TCKN Validator Microservice

A microservice example for TCKN validation based on [epigra/tckimlik](https://github.com/epigra/tckimlik) 
works with [Symfony HttpFoundation](https://github.com/symfony/http-foundation).

[![tckn-validate asciicast](https://asciinema.org/a/269514.svg)](https://asciinema.org/a/269514)

## Install
Simply, install dependencies with `composer install` then it's will be alive!

## Example
```shell
# Start server
php -S localhost:8000

# Send request with cURL
curl \
  --verbose \
  --request POST \
  --header "Content-Type: application/json" \
  --data '{"identity":"11111111111", "name":"john", "surname": "doe", "birth": 1980}' \
  localhost:8000
  
# Or more simple request with HTTPie
http -v localhost:8000 identity=11111111111 name=john surname=doe birth=1980
```

