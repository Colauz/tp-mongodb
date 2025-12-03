<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use MongoDB\BSON\ObjectId; // <--- TRÈS IMPORTANT : Nécessaire pour convertir les IDs

// Initialisation des services
$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient();
$es = getElasticSearchClient();

$list = [];
$searchQuery = $_GET['q'] ?? null;

if ($searchQuery) {
    $params = [
        'index' => 'bibliotheque',
        'body'  => [
            'query' => [
                'multi_match' => [
                    'query'     => $searchQuery,
                    'fields'    => ['titre', 'auteur'],
                    'fuzziness' => 'AUTO'
                ]
            ]
        ]
    ];

    try {
        $response = $es->search($params);

        $ids = [];
        foreach ($response['hits']['hits'] as $hit) {
            $ids[] = new ObjectId($hit['_id']);
        }

        if (count($ids) > 0) {
            $list = $manager->tp->find(['_id' => ['$in' => $ids]])->toArray();
        } else {
            $list = [];
        }

    } catch (Exception $e) {
        $list = [];
    }
}
else {
    $cacheKey = 'liste_manuscrits';

    if ($redis && $redis->exists($cacheKey)) {
        $json = $redis->get($cacheKey);
        $list = json_decode($json, true);
    }

    else {
        $list = $manager->tp->find([])->toArray();

        if ($redis) {
            $redis->set($cacheKey, json_encode($list), 'EX', 60);
        }
    }
}

try {
    echo $twig->render('index.html.twig', [
        'list' => $list,
        'search_query' => $searchQuery
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}