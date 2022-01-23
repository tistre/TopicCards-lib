# TopicCards-lib

[TopicCards](https://topiccards.net/) Neo4j backend (PHP)

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
$script = new StrehleDe\TopicCards\Import\SimpleImportScript('bolt', 'bolt://neo4j:SECRET@host.docker.internal');
$script->convertFileToCypher($argv[1]);
```