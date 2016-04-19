<?php

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();

    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

/**
 * Setting up the view component
 */
$di->set('view', function () use ($config) {
    $view = new View();

    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.volt' => function ($view, $di) use ($config) {

            $volt = new VoltEngine($view, $di);

            $volt->setOptions([
                'compiledPath' => $config->application->cache->voltDir,
                'compiledSeparator' => '_'
            ]);

            return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php',
        '.php' => 'Phalcon\Mvc\View\Engine\Php'
    ]);

    return $view;
}, true);

/**
 * View cache
 */
$di->set('viewCache', function () use ($config) {
    if (!$config->application->production) {
        $frontCache = new \Phalcon\Cache\Frontend\None();
    } else {
        // By default cache data for one day
        $frontCache = new \Phalcon\Cache\Frontend\Output([
            'lifetime' => 86400,
        ]);
    }

    return new \Phalcon\Cache\Backend\File($frontCache, [
        'cacheDir' => $config->application->cache->viewsDir,
        'prefix' => $config->application->cache->viewsPrefix,
    ]);
});

/**
 * Configuracion de los namepsace de los controllers
 */
$di->set('dispatcher', function () use ($di, $config) {
    $dispatcher = new Dispatcher();

    //set default namespace
    $dispatcher->setDefaultNamespace($config->namespace->controller);

    //in production
    if ($config->application->production) {
        //set event for 404
        $eventsManager = $di->getShared('eventsManager');

        $eventsManager->attach(
            'dispatch:beforeException',
            function ($event, $dispatcher, $exception) {
                switch ($exception->getCode()) {
                    case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                    case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    default:
                        $dispatcher->forward([
                            'controller' => 'index',
                            'action' => 'notFound'
                        ]);

                        return false;
                }
            }
        );

        $dispatcher->setEventsManager($eventsManager);
    }

    return $dispatcher;
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config, $di) {
    // Create connection
    $connection = new DbAdapter([
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => $config->database->charset
    ]);

    //profile sql queries
    if ($config->application->debug->logQueries) {
        $eventsManager = new \Phalcon\Events\Manager();

        $logger = new FileLogger($config->application->logsDir . $config->database->debugLog);

        //Listen all the database events
        $eventsManager->attach('db', function ($event, $connection) use ($logger) {
            if ($event->getType() == 'beforeQuery') {
                $sqlVariables = $connection->getSQLVariables();
                if (count($sqlVariables)) {
                    $logger->log(
                        $connection->getSQLStatement(). ' BINDS => ' . join(', ', $sqlVariables),
                        Logger::INFO
                    );
                } else {
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
$di->set('logger', function () use ($config, $di) {
    return new FileLogger($config->application->logsDir . $config->application->errorLog);
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () use ($config) {
    if (!$config->application->production) {
        return new MetaDataAdapter();
    }

    return new MetaDataAdapter([
        'metaDataDir' => $config->application->cache->metadataDir
    ]);
}, true);

/**
 * Set the models cache service
 * Cache for models
 */
$di->set('modelsCache', function () use ($config) {
    // Don't cache if we are not in production
    if (!$config->application->production) {
        $frontCache = new \Phalcon\Cache\Frontend\None();
        $cache = new Phalcon\Cache\Backend\Memory($frontCache);
    } else {
        // By default cache data for 1 day
        $frontCache = new \Phalcon\Cache\Frontend\Data([
            'lifetime' => 86400
        ]);

        // Memcached connection settings
        $cache = new \Phalcon\Cache\Backend\Memcache($frontCache, [
            'host' => $config->memcache->host,
            'port' => $config->memcache->port
        ]);
    }

    return $cache;
});

/**
 * Configuracion de los routers
 */
$di->set('router', function () {
    return include __DIR__ . '/routes.php';
}, true);

/**
 * Start the session the first time some component request the session service
 */
$di->set('session', function () use ($config) {
    $memcache = new \Phalcon\Session\Adapter\Memcache([
        // mandatory
        'host' => $config->memcache->host,
        // optional (standard: 11211)
        'port' => $config->memcache->port,
        // optional (standard: 7200)
        'lifetime' => 7200,
        // optional (standard: [empty_string])
        'prefix' => strtolower($config->application->siteName) . '-',
        // mandatory in this case, set to false as permanent sessions are not desirable
        'persistent' => false
    ]);

    //only start the session if its not already started
    if (!isset($_SESSION)) {
        $memcache->start();
    }

    return $memcache;
});

/**
 * Config the default cache storage
 */
$di->set('cache', function () use ($config) {
    //Create a Data frontend and set a default lifetime to 1 hour
    $frontend = new Phalcon\Cache\Frontend\Data([
        'lifetime' => 3600
    ]);

    // Set up Memcached and use tracking to be able to clean it later.
    // You should not use tracking if you're going to store a lot of keys!
    $cache = new Phalcon\Cache\Backend\Memcache($frontend, [
        'host' => $config->memcache->host,
        'port' => $config->memcache->port
    ]);

    return $cache;
});

/**
 * Config redis
 */
$di->set('redis', function () use ($config) {
    //Connect to redis
    $redis = new Redis();
    $redis->connect($config->redis->host, $config->redis->port);
    $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

    return $redis;
});

/**
 * config queue by default Beanstalkd
 */
$di->set('queue', function () use ($config) {
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
$di->set('flash', function () {
    return new \Phalcon\Flash\Session();
});

/**
 * Hmlt purifier
 */
$di->set('purifier', function () use ($config) {
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
$di->set('MobileDetect', function () {
    return new \Detection\MobileDetect();
});

/**
 * config elastic search
 */
$di->set('elasticSearch', function () use ($config) {
    //Connect to the queue
    $client = new \Elasticsearch\Client([
        'hosts' => [$config->elasticSearch->hosts],
    ]);

    return $client;
});
