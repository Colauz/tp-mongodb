<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

$message = null;

// Si le formulaire a été soumis (méthode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // On récupère les données du formulaire
    $data = [
        'cote' => $_POST['cote'] ?? '',
        'titre' => $_POST['titre'] ?? '',
        'auteur' => $_POST['auteur'] ?? '',
        'siecle' => $_POST['siecle'] ?? ''
    ];

    // Insertion dans la collection 'tp'
    try {
        $result = $manager->tp->insertOne($data);

        // Si l'insertion a marché (on a un ID inséré), on redirige vers l'accueil
        if ($result->getInsertedCount() > 0) {
            header('Location: index.php');
            exit;
        } else {
            $message = "Erreur lors de l'ajout.";
        }
    } catch (Exception $e) {
        $message = "Erreur technique : " . $e->getMessage();
    }
}

// Affichage du template
try {
    echo $twig->render('create.html.twig', ['message' => $message]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}