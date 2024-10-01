<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class Env
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Env();
        }

        return self::$instance;
    }

    public static function get($key)
    {
        return $_ENV[$key] ?? null;
    }
}

?>