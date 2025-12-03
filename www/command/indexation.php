<?php

require_once __DIR__ . '/../init.php';

$mongo = getMongoDbManager();
$es = getElasticSearchClient();

echo "Début de l'indexation...\n";

$collection = $mongo->tp;
$cursor = $collection->find([]);

foreach ($cursor as $document) {
    $id = (string)$document->_id;

    $params = [
        'index' => 'bibliotheque',
        'id'    => $id,
        'body'  => [
            'titre'  => $document->titre ?? 'Sans titre',
            'auteur' => $document->auteur ?? 'Inconnu',
            'cote'   => $document->cote ?? '',
            'siecle' => $document->siecle ?? ''
        ]
    ];

    try {
        $es->index($params);
        echo "Livre indexé : " . ($document->titre ?? 'N/A') . "\n";
    } catch (Exception $e) {
        echo "Erreur sur le livre $id : " . $e->getMessage() . "\n";
    }
}

echo "Indexation terminée !\n";