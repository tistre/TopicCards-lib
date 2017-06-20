<?php

require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function($class)
{
    // project-specific namespace prefix
    $prefix = 'TopicCards\\';

    // base directory for the namespace prefix
    $baseDir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (substr($class, 0, $len) !== $prefix)
    {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relativeClass = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $fileName = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // if the file exists, require it
    if (file_exists($fileName))
    {
        require $fileName;
    }
});

$config = Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . '/phpunit_bootstrap.yaml'));

$logger = new \Monolog\Logger('TopicCards');
$logger->pushHandler(new \Monolog\Handler\StreamHandler($config['logger']['path'], \Monolog\Logger::DEBUG));

$db = new TopicCards\Db\Db($config['neo4j']);
$search = new TopicCards\Search\Search($config['elasticsearch']);

$tmSystem = new TopicCards\Model\TopicMapSystem();
$topicMap = $tmSystem->newTopicMap('default');

$topicMap->setSearch($search);
$topicMap->setDb($db);
$topicMap->setLogger($logger);
$topicMap->setUrl($config['topicmap']['url']);
