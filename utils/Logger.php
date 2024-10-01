<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

class LoggerSingleton
{
    private static $logger;

    public static function getInstance()
    {
        if (self::$logger === null) {
            self::$logger = new Logger('app');
            self::$logger->pushHandler(new StreamHandler(__DIR__ . '/app.log', Level::Debug));
        }

        return self::$logger;
    }
}
