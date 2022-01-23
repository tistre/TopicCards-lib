# TopicCards-lib

[TopicCards](https://topiccards.net/) Neo4j backend (PHP)

## Quick test with Docker

Build the PHP image that meets this library's requirements:

```
$ docker build docker/php -t tistre/topiccards-php
```

Install dependencies:
```
$ docker run --rm --interactive --tty \
  --volume $PWD:/app --volume ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
  --workdir /app tistre/topiccards-php composer install
```
