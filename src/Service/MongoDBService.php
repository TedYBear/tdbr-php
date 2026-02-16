<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;

class MongoDBService
{
    private Client $client;
    private Database $database;
    private string $databaseName;

    public function __construct(string $mongoUri, string $databaseName)
    {
        $this->databaseName = $databaseName;
        $this->client = new Client($mongoUri);
        $this->database = $this->client->selectDatabase($databaseName);
    }

    /**
     * Get a MongoDB collection
     */
    public function getCollection(string $collectionName): Collection
    {
        return $this->database->selectCollection($collectionName);
    }

    /**
     * Get the database instance
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Get the client instance
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Check if MongoDB connection is working
     */
    public function ping(): bool
    {
        try {
            $this->client->selectDatabase('admin')->command(['ping' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
