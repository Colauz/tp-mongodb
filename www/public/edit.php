<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient();
$es = getElasticSearchClient(); // <--- Client ES

$id = $_GET['id'] ?? null;
$message = null;

if (!$id) {
    header('Location: app.php');
    exit;
}

try {
    $document = $manager->tp->findOne(['_id' => new ObjectId($id)]);
} catch (Exception $e) {
    die("ID invalide");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'cote' => $_POST['cote'],
        'titre' => $_POST['titre'],
        'auteur' => $_POST['auteur'],
        'siecle' => $_POST['siecle']
    ];

    try {
        // Update MongoDB
        $manager->tp->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );

        // --- A. SYNC ELASTICSEARCH ---
        // On écrase les infos dans ElasticSearch avec les nouvelles
        if ($es) {
            $es->index([
                'index' => 'bibliotheque',
                'id'    => $id,
                'body'  => $data
            ]);
        }

        // --- B. SYNC REDIS ---
        if ($redis) {
            $redis->del('liste_manuscrits');
        }

        header('Location: app.php');
        exit;
    } catch (Exception $e) {
        $message = "Erreur lors de la modification : " . $e->getMessage();
    }
}

try {
    echo $twig->render('edit.html.twig', [
        'document' => $document,
        'message' => $message
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}