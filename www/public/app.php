<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

// Initialisation des services
$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); // Fonction ajoutée dans init.php

$list = [];
$cacheKey = 'liste_manuscrits'; // La clé unique pour stocker nos données dans Redis

// --- LOGIQUE DE CACHE ---

// 1. On vérifie d'abord si les données sont dans le cache Redis
// On s'assure que $redis n'est pas null (au cas où la connexion échoue ou est désactivée)
if ($redis && $redis->exists($cacheKey)) {
    // CAS 1 : DONNÉES EN CACHE (RAPIDE)
    // Redis renvoie une chaîne de caractères (JSON), on la décode en tableau PHP
    $json = $redis->get($cacheKey);
    $list = json_decode($json, true);
}
else {
    // CAS 2 : PAS DE CACHE (LENT - SOURCE DE VÉRITÉ)
    // On doit interroger MongoDB
    $collection = $manager->tp; // On cible la collection 'tp'
    $cursor = $collection->find([]); // On récupère tous les documents
    $list = $cursor->toArray(); // On convertit le curseur en tableau PHP simple

    // 3. Mise en cache pour la prochaine fois
    if ($redis) {
        // Redis ne stocke que des chaînes, on encode notre tableau en JSON
        // 'EX', 60 signifie que le cache expirera automatiquement dans 60 secondes
        $redis->set($cacheKey, json_encode($list), 'EX', 60);
    }
}

// --- AFFICHAGE ---

// On passe la liste (qu'elle vienne de Redis ou de Mongo) au template
try {
    echo $twig->render('index.html.twig', ['list' => $list]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}