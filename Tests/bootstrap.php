<?php

define('PROJECT_BASE',realpath(__DIR__.'/../../../../'));
require_once PROJECT_BASE . '/vendor/symfony/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', PROJECT_BASE);
$loader->register();

spl_autoload_register(function($class) {
    if (0 === strpos($class, 'RickySu\\TagCacheBundle\\')) {
        $path = implode('/', array_slice(explode('\\', $class), 2)).'.php';
        require_once __DIR__.'/../'.$path;

        return true;
    }
});
