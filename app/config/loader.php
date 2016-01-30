<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */

$loader->registerNamespaces(array(
    $config->namespace->controller => $config->application->controllersDir,
    $config->namespace->models => $config->application->modelsDir,
    $config->namespace->library => $config->application->libraryDir,
));

$loader->register();
