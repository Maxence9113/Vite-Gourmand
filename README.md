# Bordeaux Gourmant

Application web de gestion de restaurant développée avec Symfony.

## Prérequis

- PHP 8.2 ou supérieur avec extension `mongodb`
- Composer
- MySQL/MariaDB
- MongoDB 8.x ou supérieur
- Serveur web (Apache/Nginx) ou Symfony CLI

## Installation

### 1. Cloner le projet

```bash
git clone <url-du-repo>
cd bordeauxgourmant
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration des bases de données

Copier le fichier `.env` et créer un `.env.local` :

```bash
cp .env .env.local
```

Modifier les lignes suivantes dans `.env.local` avec vos identifiants :

**MariaDB (données principales)** :
```
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=10.11.14-MariaDB&charset=utf8mb4"
```

**MongoDB (statistiques)** :
```
MONGODB_URI=mongodb://mongo_user:mongo_password@localhost:27017/mongo_db?authSource=mongo_db
MONGODB_DB=mongo_db
```

Pour créer l'utilisateur MongoDB, voir [database/mongo/README.md](database/mongo/README.md)

### 4. Créer la base de données MariaDB

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**MongoDB** : Pas de migrations nécessaires, les collections sont créées automatiquement.

Pour tester la connexion MongoDB :
```bash
php bin/console test:mongodb
```

### 5. Charger les fixtures (données de test)

```bash
php bin/console doctrine:fixtures:load
```

### 6. Configurer les permissions pour les uploads

**Important** : Le répertoire des uploads doit être accessible en écriture par le serveur web.

```bash
# Sur Linux/Mac
chmod 775 public/uploads/recipe_illustrations
chgrp www-data public/uploads/recipe_illustrations  # ou le groupe de votre serveur web

# Alternative si vous ne connaissez pas le groupe
chmod 777 public/uploads/recipe_illustrations  # À utiliser UNIQUEMENT en développement
```

### 7. Lancer le serveur de développement

```bash
symfony serve
# ou
php -S localhost:8000 -t public
```

L'application sera accessible sur `http://localhost:8000`

## Fonctionnalités

- **Gestion des utilisateurs** : Inscription, connexion, profils
- **Gestion des recettes** : CRUD complet avec upload d'illustrations
- **Gestion des catégories** : Organisation des recettes
- **Gestion des allergènes** : Association avec les recettes
- **Interface d'administration** : Dashboard et gestion complète

## Comptes de test

Après avoir chargé les fixtures, vous pouvez utiliser ces comptes :

- **Admin** : `admin@example.com` / `password`
- **User** : `user@example.com` / `password`

## Structure du projet

```
src/
├── Controller/       # Contrôleurs
├── Entity/          # Entités Doctrine
├── Form/            # Formulaires
├── Repository/      # Repositories
└── Service/         # Services métier
templates/           # Templates Twig
assets/              # JavaScript et CSS
public/
├── uploads/         # Fichiers uploadés (non versionné)
└── ...
```

## Technologies utilisées

- Symfony 6.4
- Doctrine ORM (MariaDB)
- Doctrine MongoDB ODM (MongoDB)
- Twig
- Bootstrap 5
- AssetMapper

## Support

Pour toute question, contactez : <votre-email>