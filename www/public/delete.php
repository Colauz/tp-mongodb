<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;

$manager = getMongoDbManager();
$redis = getRedisClient();
$es = getElasticSearchClient(); // <--- Client ES
$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Delete MongoDB
        $manager->tp->deleteOne(['_id' => new ObjectId($id)]);

        // --- A. SYNC ELASTICSEARCH ---
        if ($es) {
            try {
                $es->delete([
                    'index' => 'bibliotheque',
                    'id'    => $id
                ]);
            } catch (Exception $e) {
                // On ignore si l'ID n'existait pas dans ES
            }
        }

        // --- B. SYNC REDIS ---
        if ($redis) {
            $redis->del('liste_manuscrits');
        }

    } catch (Exception $e) {
        // Erreur silencieuse
    }
}

header('Location: app.php');
exit;