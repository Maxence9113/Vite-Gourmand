# Tests MongoDB - OrderStats

Ce document explique comment exécuter les tests liés aux statistiques de commandes stockées dans MongoDB.

## Prérequis

Pour exécuter les tests d'intégration MongoDB, vous devez avoir :

1. **MongoDB installé et démarré** sur votre machine locale
2. **Configuration de l'environnement de test**

## Configuration

### 1. Installer MongoDB (si non installé)

#### Sur Ubuntu/Debian:
```bash
sudo apt-get install mongodb
sudo systemctl start mongodb
```

#### Sur macOS (avec Homebrew):
```bash
brew install mongodb-community
brew services start mongodb-community
```

#### Sur Windows:
Téléchargez et installez depuis [mongodb.com](https://www.mongodb.com/try/download/community)

### 2. Configurer l'environnement de test

Assurez-vous que votre fichier `.env.test` contient les bonnes variables MongoDB :

```bash
# MongoDB de test
MONGODB_URI=mongodb://localhost:27017
MONGODB_DB=bordeauxgourmant_test
```

### 3. Sans authentification (développement local)

Si votre MongoDB local n'a pas d'authentification activée, utilisez simplement :

```bash
MONGODB_URI=mongodb://localhost:27017
```

### 4. Avec authentification

Si votre MongoDB nécessite une authentification, créez d'abord un utilisateur de test :

```javascript
// Dans le shell Mongo
use bordeauxgourmant_test
db.createUser({
  user: "test_user",
  pwd: "test_password",
  roles: [{ role: "readWrite", db: "bordeauxgourmant_test" }]
})
```

Puis mettez à jour `.env.test` :

```bash
MONGODB_URI=mongodb://test_user:test_password@localhost:27017/bordeauxgourmant_test
MONGODB_DB=bordeauxgourmant_test
```

## Exécution des tests

### Tests unitaires (ne nécessitent PAS MongoDB)

Ces tests utilisent des mocks et fonctionnent toujours :

```bash
# Test du service OrderStatsService
php bin/phpunit tests/Service/OrderStatsServiceTest.php

# Ce test passe toujours car il utilise des mocks
```

Résultat attendu : ✅ 6 tests passent (6 assertions)

### Tests d'intégration (nécessitent MongoDB)

Ces tests utilisent une vraie base MongoDB et seront **skippés** si MongoDB n'est pas disponible :

```bash
# Test du repository OrderStatsRepository
php bin/phpunit tests/Repository/OrderStatsRepositoryTest.php

# Test du contrôleur OrderStatsController
php bin/phpunit tests/Controller/Admin/OrderStatsControllerTest.php
```

#### Si MongoDB est disponible :
- ✅ Tous les tests passent
- Les tests créent et nettoient automatiquement les données

#### Si MongoDB n'est PAS disponible :
- ⚠️ Les tests sont automatiquement skippés
- Message : "MongoDB is not available or not configured"
- C'est **normal** en développement sans MongoDB

## Tests implémentés

### 1. OrderStatsServiceTest (tests unitaires)
- ✅ Création de nouvelles statistiques
- ✅ Mise à jour de statistiques existantes
- ✅ Gestion des erreurs
- ✅ Suppression de statistiques
- ✅ Gestion des cas limites

### 2. OrderStatsRepositoryTest (tests d'intégration)
- Récupération du nombre de commandes par menu
- Filtrage par nom de menu
- Filtrage par période
- Calcul du chiffre d'affaires par menu
- Statistiques globales
- Récupération des menus et thèmes distincts
- Agrégation par thème

### 3. OrderStatsControllerTest (tests fonctionnels)
- Contrôle d'accès (authentification requise)
- Vérification des rôles (admin/employé uniquement)
- Affichage des statistiques globales
- Filtrage par menu et par thème
- Filtrage par période (semaine, mois, personnalisé)
- Gestion des dates invalides
- Rendu des graphiques Chart.js

## Dépannage

### Erreur: "Command drop requires authentication"

Cela signifie que MongoDB nécessite une authentification. Soit:
1. Désactivez l'authentification pour le développement local
2. Créez un utilisateur comme indiqué dans la section "Avec authentification"

### Erreur: "Failed to parse MongoDB URI"

Vérifiez que :
1. `MONGODB_URI` est bien défini dans `.env.test`
2. Le format de l'URI est correct : `mongodb://localhost:27017`

### Les tests sont skippés automatiquement

C'est normal si MongoDB n'est pas installé. Les tests principaux (Service) fonctionnent toujours car ils n'en ont pas besoin.

## Structure des tests

```
tests/
├── Service/
│   └── OrderStatsServiceTest.php       ← Tests unitaires (toujours exécutables)
├── Repository/
│   └── OrderStatsRepositoryTest.php    ← Tests d'intégration (nécessitent MongoDB)
└── Controller/Admin/
    └── OrderStatsControllerTest.php    ← Tests fonctionnels (nécessitent MongoDB)
```

## Couverture de tests

Les tests couvrent :
- ✅ La logique métier du service
- ✅ Les requêtes d'agrégation MongoDB
- ✅ Les filtres et tris
- ✅ La sécurité et le contrôle d'accès
- ✅ Les cas d'erreur et limites
- ✅ L'affichage dans l'interface admin

## CI/CD

Pour l'intégration continue, configurez MongoDB dans votre pipeline :

```yaml
# Exemple GitLab CI
services:
  - mongo:latest

variables:
  MONGODB_URI: mongodb://mongo:27017
  MONGODB_DB: bordeauxgourmant_test
```