# Bordeaux Gourmant

Application web de gestion de restaurant développée avec Symfony.

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL/MariaDB
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

### 3. Configuration de la base de données

Copier le fichier `.env` et créer un `.env.local` :

```bash
cp .env .env.local
```

Modifier la ligne `DATABASE_URL` dans `.env.local` avec vos identifiants :

```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/bordeauxgourmant?serverVersion=8.0"
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
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

- Symfony 7.x
- Doctrine ORM
- Twig
- Bootstrap 5
- AssetMapper

## Support

Pour toute question, contactez : <votre-email>