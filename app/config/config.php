<?php

/**
 * Autoloader composer
 */
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../../');
$dotenv->load();

return new \Phalcon\Config([
    'database' => [
        'adapter' => 'Mysql',
        'host' => getenv('DATABASE_HOST'),
        'username' => getenv('DATABASE_USER'),
        'password' => getenv('DATABASE_PASS'),
        'dbname' => getenv('DATABASE_NAME'),
        'charset' => getenv('DATABASE_CHARSET'),
        'debugLog' => getenv('QUERY_DEBUG_LOG'),
    ],
    'application' => [
        'siteName' => getenv('SITE_NAME'),
        'controllersDir' => __DIR__ . '/../../app/controllers/',
        'modelsDir' => __DIR__ . '/../../app/models/',
        'viewsDir' => __DIR__ . '/../../app/views/',
        'libraryDir' => __DIR__ . '/../../app/library/',
        'logsDir' => __DIR__ . '/../../app/logs/',
        'baseUri' => '/',
        'domain' => getenv('DOMAIN'),
        'production' => getenv('PRODUCTION'),
        'errorLog' => getenv('ERROR_LOG'),
        'cache' => [
            'baseDir' => __DIR__ . '/../../app/cache/',
            'voltDir' => __DIR__ . '/../../app/cache/volt/',
            'viewsDir' => __DIR__ . '/../../app/cache/views/',
            'viewsPrefix' => getenv('VIEWS_CACHE_PREFIX'),
            'metadataDir' => __DIR__ . '/../../app/cache/metaData/',
        ],
        'debug' => [
            'profile' => getenv('DEBUG_PROFILE'),
            'logQueries' => getenv('DEBUG_QUERY'),
        ],
    ],
    'namespace' => [
        'controller' => 'Phalcon\Controllers',
        'models' => 'Phalcon\Models',
        'library' => 'Phalcon',
    ],
    'beanstalk' => [
        'host' => getenv('BEANSTALK_HOST'),
        'port' => getenv('BEANSTALK_PORT'),
        'prefix' => getenv('BEANSTALK_PREFIX'),
    ],
    'memcache' => [
        'host' => getenv('MEMCACHE_HOST'),
        'port' => getenv('MEMCACHE_PORT'),
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST'),
        'port' => getenv('REDIS_HOST'),
    ],
    'elasticSearch' => [
        'hosts' => getenv('ELASTIC_HOST'),
    ],
    'email' => [
        'host' => getenv('EMAIL_HOST'),
        'port' => getenv('EMAIL_PORT'),
        'username' => getenv('EMAIL_USER'),
        'password' => getenv('EMAIL_PASS'),
    ],
]);
