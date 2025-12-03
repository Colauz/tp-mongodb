<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient();
$es = getElasticSearchClient(); // <--- On récupère le client ElasticSearch

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'cote' => $_POST['cote'] ?? '',
        'titre' => $_POST['titre'] ?? '',
        'auteur' => $_POST['auteur'] ?? '',
        'siecle' => $_POST['siecle'] ?? ''
    ];

    try {
        // Insertion MongoDB
        $result = $manager->tp->insertOne($data);

        if ($result->getInsertedCount() > 0) {

            // --- A. SYNC ELASTICSEARCH ---
            // On indexe immédiatement le nouveau livre
            if ($es) {
                $es->index([
                    'index' => 'bibliotheque',
                    'id'    => (string)$result->getInsertedId(), // On récupère l'ID créé par Mongo
                    'body'  => $data
                ]);
            }

            // --- B. SYNC REDIS ---
            if ($redis) {
                $redis->del('liste_manuscrits');
            }

            header('Location: app.php');
            exit;
        } else {
            $message = "Erreur lors de l'ajout.";
        }
    } catch (Exception $e) {
        $message = "Erreur technique : " . $e->getMessage();
    }
}

try {
    echo $twig->render('create.html.twig', ['message' => $message]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}