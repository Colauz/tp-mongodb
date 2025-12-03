<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/vendor/autoload.php';

use MongoDB\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Elastic\Elasticsearch\ClientBuilder;

// env configuration
(Dotenv\Dotenv::createImmutable(__DIR__))->load();

function getTwig(): Environment
{
    // twig configuration
    return new Environment(new FilesystemLoader('../templates'));
}

function getMongoDbManager(): Database
{
    $client = new MongoDB\Client("mongodb://{$_ENV['MDB_USER']}:{$_ENV['MDB_PASS']}@{$_ENV['MDB_SRV']}:{$_ENV['MDB_PORT']}");
    return $client->selectDatabase($_ENV['MDB_DB']);
}

function getRedisClient() {
    // Si le cache est désactivé, on renvoie null
    if (empty($_ENV['REDIS_ENABLE']) || $_ENV['REDIS_ENABLE'] == 'false') {
        return null;
    }

    try {
        return new Predis\Client([
            'scheme' => 'tcp',
            'host'   => $_ENV['REDIS_HOST'],
            'port'   => $_ENV['REDIS_PORT'],
        ]);
    } catch (Exception $e) {
        return null;
    }
}

function getElasticSearchClient() {
    try {
        return ClientBuilder::create()
            ->setHosts([$_ENV['ELASTIC_HOST'] . ':' . $_ENV['ELASTIC_PORT']])
            ->build();
    } catch (Exception $e) {
        return null;
    }
}