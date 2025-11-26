<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

$id = $_GET['id'] ?? null;
$message = null;

// Vérification de l'ID
if (!$id) {
    header('Location: index.php');
    exit;
}

// Récupération du document existant pour pré-remplir le formulaire
try {
    $document = $manager->tp->findOne(['_id' => new ObjectId($id)]);
} catch (Exception $e) {
    die("ID invalide");
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'cote' => $_POST['cote'],
        'titre' => $_POST['titre'],
        'auteur' => $_POST['auteur'],
        'siecle' => $_POST['siecle']
    ];

    try {
        // Mise à jour avec updateOne
        // $set permet de ne modifier que les champs spécifiés
        $manager->tp->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );

        // Redirection vers la liste après succès
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $message = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Affichage du template
try {
    echo $twig->render('update.html.twig', [
        'document' => $document,
        'message' => $message
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}