<?php

spl_autoload_register(function ($class) {
    static $prefix = 'Vanderlee\\Sentence\\';
    if (stripos($class, $prefix) === 0) {
        $file = str_replace('\\', DIRECTORY_SEPARATOR,
            dirname(__DIR__).'\\src\\'.str_ireplace($prefix, '', $class).'.php');
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
        }
    }
});