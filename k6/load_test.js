import http from 'k6/http';
import { check, sleep } from 'k6';

// Configuration du test : on monte à 20 utilisateurs simultanés
export const options = {
    stages: [
        { duration: '10s', target: 5 },  // Montée en charge tranquille
        { duration: '20s', target: 20 }, // Pic de charge (20 utilisateurs en même temps)
        { duration: '10s', target: 0 },  // Descente
    ],
};

const BASE_URL = 'http://tpmongo-php'; // Nom du conteneur dans le réseau Docker

export default function () {
    // 1. Visiter la page d'accueil (Liste des livres)
    // C'est là que le cache Redis est le plus utile !
    let res = http.get(`${BASE_URL}/app.php`);

    check(res, {
        'status est 200': (r) => r.status === 200,
        'Page contient "Liste des manuscrits"': (r) => r.body.includes('Liste des manuscrits'),
    });

    // 2. Simuler un temps de lecture (comportement humain)
    sleep(1);

    // 3. Ajouter un livre (POST)
    // On génère un titre aléatoire pour ne pas avoir de doublons
    let randomId = Math.floor(Math.random() * 10000);
    let payload = {
        cote: `TEST-${randomId}`,
        titre: `Livre de Test k6 ${randomId}`,
        auteur: 'Robot k6',
        siecle: '21'
    };

    let resAdd = http.post(`${BASE_URL}/create.php`, payload);

    check(resAdd, {
        // create.php redirige (302) ou affiche (200) selon votre code, on accepte les deux
        'Ajout passé': (r) => r.status === 200 || r.status === 302,
    });

    sleep(1);
}