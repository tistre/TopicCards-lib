<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(function($class)
{
    // project-specific namespace prefix
    $prefix = 'TopicCards\\';

    // base directory for the namespace prefix
    $base_dir = dirname(__DIR__) . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (substr($class, 0, $len) !== $prefix)
    {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $filename = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($filename))
    {
        require $filename;
    }
});

$db_params =
    [
        'connections' =>
            [
                'default' => 'http://neo4j:dc@localhost:7474',
                'bolt' => 'bolt://neo4j:dc@localhost:7687'
            ]
    ];

$search_params =
    [
        'connection' => [ ],
        'index' => 'xddb'
    ];

$logger = new \Monolog\Logger('TopicCards');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('/var/log/topiccards.log', \Monolog\Logger::DEBUG));

$db = new TopicCards\Db\Db($db_params);
$search = new TopicCards\Search\Search($search_params);

$tm_system = new TopicCards\Model\TopicMapSystem();
$topicmap = $tm_system->newTopicMap('default');

$topicmap->setSearch($search);
$topicmap->setDb($db);
$topicmap->setLogger($logger);
$topicmap->setUrl('http://www.strehle.de/tim/topicmaps/xddb');
