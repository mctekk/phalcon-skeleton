<?php

//local env variables
Dotenv::load(__DIR__.'/../../');

return new \Phalcon\Config(array(
	'database' =>  [
 		'adapter'     => 'Mysql',
        'host'        => getenv('DBHOST'),
        'username'    => getenv('DBUSER'),
        'password'    => getenv('DBPASS'),
        'dbname'      => getenv('DB')
	],
	'application' => [
		'controllersDir' => __DIR__ . '/../../app/controllers/',
		'modelsDir'      => __DIR__ . '/../../app/models/',
		'viewsDir'       => __DIR__ . '/../../app/views/',
		'pluginsDir'     => __DIR__ . '/../../app/plugins/',
		'libraryDir'     => __DIR__ . '/../../app/library/',
		'cacheDir'       => __DIR__ . '/../../app/cache/',
		'voltDir'       => __DIR__ . '/../../app/cache/volt',
		'baseUri'        => '/',
        'domain'		=> getenv('DOMAIN'),
        'production'     => getenv('PRODUCTION'),
		'debug'           => ['profile' => false, 'logQueries' => false],
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
