<?php

//comments this line in production
use RuntimeException as Exception;
use Phalcon\Http\Response;
use Phalcon\Logger\Adapter\File as Logger;

try 
{

	define('APP_PATH', realpath('..'));
	
	/**
	 * Autoloader composer
	 */
	require_once __DIR__ . '/../vendor/autoload.php';

	/**
	 * Read the configuration
	 */
	$config = include __DIR__ . "/../app/config/config.php";

	//debug
	if(!$config->application->production)
	{
		$debug = new \Phalcon\Debug();
		$debug->listen();
	}
	else
	{
		error_reporting(0);

	}

	/**
	 * Read auto-loader
	 */
	include __DIR__ . "/../app/config/loader.php";

	/**
	 * Read services
	 */
	include __DIR__ . "/../app/config/services.php";

	/**
	 * Handle the request
	 */
	$application = new \Phalcon\Mvc\Application($di);
	
	echo $application->handle()->getContent();
	
} 
catch (Exception $e) 
{
	if($config->application->production)
	{
		 /**
	     * Log the exception
	     */
	    $logger = new Logger(APP_PATH . '/app/logs/error.log');
	    $logger->error($e->getMessage());
	    $logger->error($e->getTraceAsString());

	    /**
	     * Show an static error page
	     */
	    $response = new Response();
	    $response->redirect('404');
	    $response->send();
	}
	else
		echo $e->getMessage();
} 
