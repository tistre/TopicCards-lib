# TopicCards-lib

[TopicCards](https://topiccards.net/) Neo4j backend (PHP)

## Installation

Use [Composer](https://getcomposer.org/) to add this library your project’s composer.json file:

```
$ composer require strehle-de/topiccards-lib
```

## Quick test with Docker

Build the PHP image that meets this library's requirements:

```
$ docker build docker/php -t strehle-de/topiccards-php
```

Install dependencies:
```
$ docker run --rm --interactive --tty \
  --volume $PWD:/app --volume ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
  --workdir /app strehle-de/topiccards-php composer install
```

Convert XML to Cypher:
```
$neo4jClient = (new StrehleDe\TopicCards\Configuration\Configuration('config/config.yaml'))->getNeo4jClient();
$script = new StrehleDe\TopicCards\Import\SimpleImportScript($neo4jClient);
$script->convertFileToCypher($argv[1]);
```