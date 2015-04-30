<?php

use Phalcon\DI\FactoryDefault,
	Phalcon\Mvc\View,
	Phalcon\Mvc\Url as UrlResolver,
	Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter,
	Phalcon\Mvc\View\Engine\Volt as VoltEngine,
	Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter,
	Phalcon\Mvc\Dispatcher as PhDispatcher,
	Phalcon\Session\Adapter\Files as SessionAdapter;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function() use ($config) {
	$url = new UrlResolver();
	$url->setBaseUri($config->application->baseUri);
	return $url;
}, true);

/**
 * Setting up the view component
 */
$di->set('view', function() use ($config) {

	$view = new View();

	$view->setViewsDir($config->application->viewsDir);

	$view->registerEngines(array(
		'.volt' => function($view, $di) use ($config) {

			$volt = new VoltEngine($view, $di);

			$volt->setOptions(array(
				'compiledPath' => $config->application->voltDir,
				'compiledSeparator' => '_'
			));

			return $volt;
		},
		'.phtml' => 'Phalcon\Mvc\View\Engine\Php',
		'.php' => 'Phalcon\Mvc\View\Engine\Php'
	));

	return $view;
}, true);


/**
 * View cache
 */
$di->set('viewCache', function () use ($config) {

        if (!$config->application->production) 
        {
            $frontCache = new \Phalcon\Cache\Frontend\None();
        } 
        else 
        {
            //Cache data for one day by default
            $frontCache = new \Phalcon\Cache\Frontend\Output(array(
                "lifetime" => 172800
            ));
        }

        return new \Phalcon\Cache\Backend\File($frontCache, array(
            "cacheDir" => APP_PATH . "/app/cache/views/",
            "prefix"   => "site-cache-"
        ));
    }
);

/**
* Configuracion de los namepsace de los controllers
*/
$di->set('dispatcher', function() use ($di, $config) {

        $dispatcher = new PhDispatcher();

        //set default namespace
        $dispatcher->setDefaultNamespace('MC\Controllers');

        //in production
        if($config->application->production)
        {
             //set event for 404
            $evManager = $di->getShared('eventsManager');

            $evManager->attach(
                'dispatch:beforeException',
                function($event, $dispatcher, $exception)
                {
                    switch ($exception->getCode()) {
                        case PhDispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                        case PhDispatcher::EXCEPTION_ACTION_NOT_FOUND:
                        default:
                            $dispatcher->forward(
                                array(
                                    'controller' => 'error',
                                    'action'     => 'show404'
                                )
                            );
                            
                        return false;
                    }
                }
            );

            $dispatcher->setEventsManager($evManager);
        }

        return $dispatcher;
    },
    true
);


/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function() use ($config, $di) {

    //db connection
    $connection = new DbAdapter(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => 'utf8'
    ));

    //profile sql queries
    if($config->application->debug['logQueries'])
    {
        $eventsManager = new \Phalcon\Events\Manager();

        $logger = new FileLogger(APP_PATH . "/app/logs/debug.log");

        //Listen all the database events
        $eventsManager->attach('db', function($event, $connection) use ($logger) {
            if ($event->getType() == 'beforeQuery') {
                $sqlVariables = $connection->getSQLVariables();
                if (count($sqlVariables)) {
                    $logger->log($connection->getSQLStatement() . ' BINDS => ' . join(', ', $sqlVariables), Logger::INFO);
                }
                else {
                    $logger->log($connection->getSQLStatement(), Logger::INFO);
                }
            }
        });

        //Assign the eventsManager to the db adapter instance
        $connection->setEventsManager($eventsManager);
    }

	return $connection;
});

/**
 * App logger for the app, so we can send the exception to this location
 */
$di->set('logger', function() use ($config, $di) {

    return new FileLogger(APP_PATH . "/app/logs/error.log");
});


/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () use ($config) {

        if (!$config->application->production) 
        {
            return new MemoryMetaDataAdapter();
        }

        return new MetaDataAdapter([
            'metaDataDir' => APP_PATH . '/app/cache/metaData/'
        ]);

    },
    true
);

/**
* Set the models cache service
* Cache for models
*/
$di->set('modelsCache', function() use ($config) {

    //si no estamos en producto 0 cache
    if (!$config->application->production) 
    {
         $frontCache = new \Phalcon\Cache\Frontend\None();
         $cache = new Phalcon\Cache\Backend\Memory($frontCache);
    }
    else
    {
        //Cache data for one day by default
        $frontCache = new \Phalcon\Cache\Frontend\Data(array(
            "lifetime" => 86400
        ));

         //Memcached connection settings
        $cache = new \Phalcon\Cache\Backend\Memcache($frontCache, array(
            "host" => $config->memcache->host,
            "port" => $config->memcache->port,
        ));
    }
   
    return $cache;
});

/**
 * Configuracion de los routers
 */
$di->set('router', function() {    
    return include __DIR__ . "/routes.php";
}, true);

/**
 * Start the session the first time some component request the session service
 */
$di->set('session', function() use ($config) {
	$memcache = new \Phalcon\Session\Adapter\Memcache(array(
        'host'          => $config->memcache->host,     // mandatory
        'post'          => $config->memcache->port,           // optional (standard: 11211)
        'lifetime'      => 8600,            // optional (standard: 8600)
        'prefix'        => $config->application->siteName,        // optional (standard: [empty_string]), means memcache key is my-app_31231jkfsdfdsfds3
        'persistent'    => false            // optional (standard: false)
    ));

    //only start the session if its not already started
    if(!isset($_SESSION))
    {
        $memcache->start();
    }

    return $memcache;

});

/**
* Config the default cache storage
*/
$di->set('cache', function() use ($config) {

    //Create a Data frontend and set a default lifetime to 1 hour
    $frontend = new Phalcon\Cache\Frontend\Data(array(
        'lifetime' => 3600
    ));

    // Set up Memcached and use tracking to be able to clean it later.
    // You should not use tracking if you're going to store a lot of keys!
    $cache = new Phalcon\Cache\Backend\Memcache($frontend, array(
        "host" => $config->memcache->host,
        "port" => $config->memcache->port,
    ));

    return $cache;
});

/**
* Config redis
*/
$di->set('redis', function() use ($config) {

    //Connect to redis
    $redis = new Redis();
    $redis->connect($config->redis->host, $config->redis->port);
    $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP); 

    return $redis;
});

/**
* config queue by default Beanstalkd
*/
$di->set('queue', function() use ($config) {

    //Connect to the queue
    $queue = new Phalcon\Queue\Beanstalk\Extended([
        'host' => $config->beanstalk->host,
        'prefix' => $config->beanstalk->prefix,
    ]);

    return $queue;
});


/**
 * Set up the flash service
 */
$di->set('flash', function() {
    return new \Phalcon\Flash\Session();
});

$di->set('purifier', function() use ($config){
    require_once($config->application->vendorDir . 'ezyang/htmlpurifier/library/HTMLPurifier.auto.php');

    $hpConfig = \HTMLPurifier_Config::createDefault();
    $hpConfig->set('HTML.Allowed', '');

    return new \HTMLPurifier($hpConfig);
});

/**
 * Mobile detetc
 * 
 * @return MobileDetect
 */
$di->set('MobileDetect', function(){
    return new \Detection\MobileDetect();
});


/**
* config elastic search
*/
$di->set('elasticSearch', function() use ($config) {

    //Connect to the queue
    $client = new \Elasticsearch\Client([
                'hosts' => [$config->elasticSearch->hosts]
            ]);

    return $client;
});