<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId; // Indispensable pour convertir l'ID string en ID Mongo
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// 1. On récupère l'ID depuis l'URL
$id = $_GET['id'] ?? null;

if ($id) {
    // 2. On cherche le document unique dans la collection 'tp'
    // MongoDB stocke les ID sous forme d'objets BSON, il faut convertir la chaîne de caractères
    try {
        $document = $manager->tp->findOne(['_id' => new ObjectId($id)]);
    } catch (Exception $e) {
        // En cas d'ID mal formaté
        $document = null;
    }
} else {
    $document = null;
}

// 3. On affiche le template
try {
    echo $twig->render('get.html.twig', ['document' => $document]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}