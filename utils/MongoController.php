<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Env.php';

use Exception;
use MongoDB\Client;
use MongoDB\Driver\ServerApi;

class MongoController
{
    private $client;
    private $db;

    public function __construct()
    {
        $uri = Env::get("MONGO_URL");
        $apiVersion = new ServerApi(ServerApi::V1);
        $this->client = new MongoDB\Client($uri, [], ['serverApi' => $apiVersion]);
        try {
            $this->client->selectDatabase('server-metadata')->command(['ping' => 1]);
            echo "Pinged your deployment. You successfully connected to MongoDB!\n";
            LoggerSingleton::getInstance()->info("Pingedd db");
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
            LoggerSingleton::getInstance()->info("Error: " . $e->getMessage());
        }

        $this->db = $this->client->selectDatabase('server-metadata');
    }

    public function getPermissions($fingerprint) {
        $collection = $this->db->selectCollection('files');

        $response = $collection->findOne(
            ['fingerprint' => $fingerprint],
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        );

        return $response ?: null;
    }
}
?>