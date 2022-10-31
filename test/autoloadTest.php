<?php

function classLoaderTest($class)
{
    $nsPrefix = 'Tos/';
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    if (strpos($path, $nsPrefix) === 0) {
        $path = substr($path, strlen($nsPrefix));
        $file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

spl_autoload_register('classLoaderTest');