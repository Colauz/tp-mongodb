<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use MongoDB\BSON\ObjectId;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient();
$es = getElasticSearchClient();

$fullList = [];
$searchQuery = $_GET['q'] ?? null;

// --- RÉCUPÉRATION DES DONNÉES (Recherche OU Cache OU Mongo) ---

if ($searchQuery) {
    // MODE RECHERCHE (ElasticSearch)
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
            $fullList = $manager->tp->find(['_id' => ['$in' => $ids]])->toArray();
        }
    } catch (Exception $e) {
        $fullList = [];
    }
}
else {
    // MODE NORMAL (Redis + Mongo)
    $cacheKey = 'liste_manuscrits';

    if ($redis && $redis->exists($cacheKey)) {
        // Redis renvoie des tableaux (Array)
        $json = $redis->get($cacheKey);
        $fullList = json_decode($json, true);
    } else {
        // Mongo renvoie des objets (BSONDocument)
        $fullList = $manager->tp->find([])->toArray();
        if ($redis) {
            $redis->set($cacheKey, json_encode($fullList), 'EX', 60);
        }
    }
}

// --- 2. NORMALISATION DES IDs ---
// On s'assure que chaque ligne a un ID "string" propre, qu'il vienne de Redis ou Mongo
$cleanList = [];
foreach ($fullList as $doc) {
    // On convertit l'objet ou le tableau en tableau simple
    $item = (array) $doc;

    // Si l'ID est un tableau (version Redis/JSON : ['$oid' => '...'])
    if (isset($item['_id']) && is_array($item['_id']) && isset($item['_id']['$oid'])) {
        $item['_id'] = $item['_id']['$oid'];
    }
    // Si l'ID est un objet Mongo (version Mongo direct)
    elseif (isset($item['_id']) && $item['_id'] instanceof ObjectId) {
        $item['_id'] = (string) $item['_id'];
    }

    $cleanList[] = $item;
}

// --- 3. PAGINATION (Le retour !) ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Nombre de livres par page
$total = count($cleanList);
$totalPages = ceil($total / $limit);

// On découpe la liste pour ne garder que la page demandée
$paginatedList = array_slice($cleanList, ($page - 1) * $limit, $limit);


// --- AFFICHAGE ---
try {
    echo $twig->render('index.html.twig', [
        'list' => $paginatedList,
        'search_query' => $searchQuery,
        'page' => $page,
        'totalPages' => $totalPages
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}