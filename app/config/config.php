<?php

//ENV Variables
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../../');
$dotenv->load();

return new \Phalcon\Config(array(
    'database' => [
        'adapter' => 'Mysql',
        'host' => getenv('DATABASE_HOST'),
        'username' => getenv('DATABASE_USER'),
        'password' => getenv('DATABASE_PASS'),
        'dbname' => getenv('DATABASE_NAME'),
    ],
    'application' => [
        'controllersDir' => __DIR__ . '/../../app/controllers/',
        'modelsDir' => __DIR__ . '/../../app/models/',
        'viewsDir' => __DIR__ . '/../../app/views/',
        'pluginsDir' => __DIR__ . '/../../app/plugins/',
        'libraryDir' => __DIR__ . '/../../app/library/',
        'cacheDir' => __DIR__ . '/../../app/cache/',
        'voltDir' => __DIR__ . '/../../app/cache/volt',
        'baseUri' => '/',
        'domain' => getenv('DOMAIN'),
        'production' => getenv('PRODUCTION'),
        'debug' => ['profile' => getenv('DEBUG_PROFILE'), 'logQueries' => getenv('DEBUG_QUERY')],
        'controllerNamespace' => 'MC\Controllers',
    ],
    'namespace' => [
        'controller' => 'MC\Controllers',
        'models' => 'MC\Models',
        'library' => 'MC',
    ],
    'beanstalk' => [
        'host' => getenv('BEANSTALK-HOST'),
        'port' => getenv('BEANSTALK-PORT'),
        'prefix' => getenv('BEANSTALK-PREFIX'),
    ],
    'memcache' => [
        'host' => getenv('MEMCACHE-HOST'),
        'port' => getenv('MEMCACHE-PORT'),
    ],
    'redis' => [
        'host' => getenv('REDIS-HOST'),
        'port' => getenv('REDIS-HOST'),
    ],
    'elasticSearch' => [
        'hosts' => null,
    ],
    'email' => [
        'host' => getenv('EMAIL-HOST'),
        'port' => getenv('EMAIL-PORT'),
        'username' => getenv('EMAIL-USER'),
        'password' => getenv('EMAIL-PASS'),
    ],
));
