# tp-mongodb

Objectifs : 

1. implémenter un crud sur une base MongoDB
2. accélérer l'application à l'aide de Redis et effectuer un test de charge avec k6 (+InfluxDB et Grafana pour visualiser)
   Lancement des tests :

```
docker compose up -d
docker compose run k6 run /scripts/load_test.js
```

3. ajouter un moteur de recherche à l'aide d'ElasticSearch

## TP 1 : MongoDB
### Architecture
- **Docker** (mode rootless) : PHP 8.3, MongoDB, Mongo Express.
- **Données** : Import des manuscrits de la bibliothèque de Clermont-Ferrand via `mongoimport` dans une collection `tp`.

### Réalisation
- **Connexion** : Driver `mongodb/mongodb` configuré dans `init.php`.
- **CRUD** :
    - Lecture : Liste des livres.
    - Ajout : Formulaire (`create.php`).
    - Modification : Mise à jour (`edit.php`).
    - Suppression : Par ID (`delete.php`).

---

## TP 2 : Cache Redis
### Objectif
Améliorer les performances avec un **cache applicatif**.

### Implémentation
- **Stack Docker** : Redis + Redis Commander.
- **Librairie** : `predis/predis`.
- **Logique** :
    - Vérification du cache avant requête MongoDB.
    - Stockage des résultats dans Redis (TTL : 60 secondes).

### Tests de Charge
- **Outils** : k6 (charge), InfluxDB (métriques), Grafana (visualisation).
- **Scénario** : Simulation d'un parcours utilisateur (`load_test.js`).
- **Résultat** : Réduction significative des temps de réponse.

---

## TP 3 : ElasticSearch
### Objectif
Ajouter une **recherche avancée** (fautes de frappe, plein texte).

### Implémentation
- **Stack** : ElasticSearch + Kibana/ElasticVue.
- **Indexation** : Script CLI (`command/indexation.php`) pour synchroniser MongoDB → ElasticSearch (index `bibliotheque`).
- **Recherche** :
    - Clause `multi_match` avec `fuzziness: 'AUTO'`.
    - Tolérance aux erreurs orthographiques.