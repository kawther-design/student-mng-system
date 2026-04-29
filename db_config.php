<?php
require_once 'vendor/autoload.php';

class Database {
    private $client;
    private $db;

    public function __construct() {
        try {
            // Check if MongoDB extension is loaded
            if (!extension_loaded('mongodb')) {
                die("MongoDB extension not loaded.");
            }
            
            // Local MongoDB connection
            $this->client = new MongoDB\Client("mongodb://localhost:27017");
            $this->db = $this->client->alhuda_school;
        } catch (Exception $e) {
            die("Could not connect to database: " . $e->getMessage());
        }
    }

    public function getCollection($name) {
        return $this->db->$name;
    }
}

$database = new Database();
?>
