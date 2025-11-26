<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// La collection a été nommée "tp" lors de votre mongoimport
$collection = $manager->tp;

// Récupération de tous les documents de la collection
// find() retourne un curseur que l'on convertit ici en tableau
$list = $collection->find([])->toArray();

// render template
try {
    echo $twig->render('index.html.twig', ['list' => $list]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}