<?php

if (!class_exists('PHPUnit_Framework_TestCase', false)
    && class_exists('PHPUnit\\Framework\\TestCase')) {
    class_alias('PHPUnit\\Framework\\TestCase', 'PHPUnit_Framework_TestCase');
}

spl_autoload_register(function ($class) {
    static $prefix = 'Vanderlee\\Sentence\\';
    if (stripos($class, $prefix) === 0) {
        $relative = str_ireplace($prefix, '', $class);
        $file = dirname(__DIR__)
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative)
            . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
});
