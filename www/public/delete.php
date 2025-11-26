<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;

$manager = getMongoDbManager();
$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Suppression du document
        $manager->tp->deleteOne(['_id' => new ObjectId($id)]);
    } catch (Exception $e) {
        // Optionnel : gérer l'erreur (ex: ID invalide)
    }
}

// Dans tous les cas, on redirige vers l'accueil
header('Location: index.php');
exit;