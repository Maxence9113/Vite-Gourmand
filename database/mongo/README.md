# MongoDB - Vite & Gourmand

Ce projet utilise **MongoDB** pour stocker les statistiques de commandes (NoSQL).

## Prérequis

- MongoDB 8.x ou supérieur installé et démarré
- Extension PHP `mongodb` installée

### Vérifier l'installation

```bash
# Vérifier que MongoDB est démarré
systemctl status mongod

# Vérifier l'extension PHP
php -m | grep mongodb
```

## Configuration

### 1. Variables d'environnement

Ajoutez ces lignes dans votre fichier `.env.local` :

```env
MONGODB_URI=mongodb://votre_user:votre_password@localhost:27017/votre_database?authSource=votre_database
MONGODB_DB=votre_database
```

### 2. Créer l'utilisateur MongoDB

Si MongoDB a l'authentification activée, créez l'utilisateur avec les droits nécessaires :

```bash
mongosh -u admin -p --authenticationDatabase admin
```

Puis dans mongosh :

```javascript
use votre_database
db.createUser({
  user: "votre_user",
  pwd: "votre_password",
  roles: [{ role: "readWrite", db: "votre_database" }]
})
```

**Note :** Si MongoDB n'a pas d'authentification, utilisez simplement :
```env
MONGODB_URI=mongodb://localhost:27017
```

## Test de connexion

Pour vérifier que Symfony se connecte bien à MongoDB :

```bash
php bin/console test:mongodb
```

Vous devriez voir :
```
✅ Connexion MongoDB réussie !
✅ Test d'écriture/lecture : OK
```

## Structure des données

Les documents MongoDB sont stockés dans le dossier `src/Document/`.

Contrairement à Doctrine ORM (MariaDB), MongoDB ODM ne nécessite pas de migrations.
Les collections sont créées automatiquement lors de la première insertion de documents.

## Documentation

- [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/)
- [MongoDB PHP Library](https://www.mongodb.com/docs/php-library/current/)
- [Symfony MongoDB Bundle](https://symfony.com/bundles/DoctrineMongoDBBundle/current/index.html)