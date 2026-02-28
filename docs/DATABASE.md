# Base de donnees et Fixtures

## Deux bases de donnees

L'application utilise **deux bases de donnees** differentes :

| Base | Technologie | ORM/ODM | Usage |
|---|---|---|---|
| **Principale** | MariaDB / MySQL | Doctrine ORM | Toutes les donnees metier |
| **Statistiques** | MongoDB | Doctrine MongoDB ODM | Graphiques et KPIs |

### Pourquoi deux bases ?

MongoDB est utilise ici a des fins **pedagogiques** pour demontrer l'integration d'une base NoSQL dans un projet Symfony. En production, les statistiques pourraient etre calculees directement depuis MariaDB.

---

## MariaDB - Base principale

### Configuration

**Fichier** : `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'    # Connexion depuis .env
    orm:
        auto_generate_proxy_classes: true      # Genere les proxies automatiquement
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                type: attribute                # Les entites utilisent des attributs PHP
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
```

**Variable d'environnement** (`.env`) :
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/vitegourmand?charset=utf8mb4"
```

### Migration

**Fichier** : `migrations/Version20260113124721.php`

Une seule migration qui cree tout le schema de la base :
- Toutes les tables (user, menu, recipe, order, etc.)
- Les cles etrangeres et contraintes
- Les index sur les colonnes frequemment interrogees
- Les contraintes d'unicite (email, orderNumber, dayOfWeek)

### Commandes utiles

```bash
# Creer la base de donnees
php bin/console doctrine:database:create

# Executer les migrations
php bin/console doctrine:migrations:migrate

# Voir le statut des migrations
php bin/console doctrine:migrations:status

# Generer une nouvelle migration (apres modification d'une entite)
php bin/console make:migration

# Valider le schema (verifier que les entites sont coherentes avec la BDD)
php bin/console doctrine:schema:validate
```

---

## MongoDB - Base de statistiques

### Configuration

**Fichier** : `config/packages/doctrine_mongodb.yaml`

```yaml
doctrine_mongodb:
    auto_generate_proxy_classes: true
    connections:
        default:
            server: '%env(resolve:MONGODB_URI)%'
    default_database: '%env(resolve:MONGODB_DB)%'
    document_managers:
        default:
            auto_mapping: true
            mappings:
                App:
                    dir: '%kernel.project_dir%/src/Document'
                    prefix: 'App\Document'
```

**Variables d'environnement** (`.env`) :
```
MONGODB_URI="mongodb://user:password@127.0.0.1:27017"
MONGODB_DB="vitegourmand_stats"
```

### Document OrderStats

**Fichier** : `src/Document/OrderStats.php`

```php
#[ODM\Document(collection: 'order_stats')]
class OrderStats
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'int')]
    private int $orderId;

    #[ODM\Field(type: 'string')]
    private string $menuName;

    #[ODM\Field(type: 'string')]
    private string $themeName;

    #[ODM\Field(type: 'float')]
    private float $totalPrice;    // En EUROS (pas en centimes !)

    #[ODM\Field(type: 'int')]
    private int $numberOfPeople;

    #[ODM\Field(type: 'date')]
    private \DateTime $orderDate;
}
```

### Repository MongoDB

**Fichier** : `src/Repository/OrderStatsRepository.php`

Contient des methodes pour interroger les statistiques :
- Filtrage par theme, menu, periode
- Agregations (totaux, moyennes)

---

## DataFixtures - Donnees de test

Les fixtures permettent de remplir la base avec des **donnees de test realistes** pour le developpement et les demonstrations.

### Ordre de chargement

Les fixtures ont des **dependances** : certaines doivent etre chargees avant d'autres.

```
1. CategoryFixtures        → Categories de recettes (Entree, Plat, Fromage, Dessert)
2. AllergenFixtures        → Allergenes (Gluten, Lactose, Arachides...)
3. DietetaryFixtures       → Regimes alimentaires (Vegan, Vegetarien, Sans gluten...)
4. ThemeFixtures           → Themes de menus (Noel, Mariage, Barbecue...)
5. RecipeFixtures          → Recettes avec illustrations
   └── Depend de : Category, Allergen
6. UserFixtures            → Utilisateurs (admin, employes, clients)
7. OpeningScheduleFixtures → Horaires d'ouverture
8. AddressFixtures         → Adresses de livraison
   └── Depend de : User
9. MenuFixtures            → Menus avec recettes et themes
   └── Depend de : Theme, Dietetary, Recipe
10. OrderFixtures          → Commandes
    └── Depend de : User, Menu, Address
11. ReviewFixtures         → Avis clients
    └── Depend de : Order
12. OrderStatsFixtures     → Stats MongoDB
    └── Depend de : Order
```

### Detail des fixtures

#### UserFixtures

**Fichier** : `src/DataFixtures/UserFixtures.php`

Cree les comptes de test :

| Type | Email | Mot de passe | Role |
|---|---|---|---|
| Admin | `jose@vitegourmand.fr` | `Admin1234!@` | ROLE_ADMIN |
| Employe | `julie@vitegourmand.fr` | `Employee123!@` | ROLE_EMPLOYEE |
| Client | `user@test.fr` | `User1234!@` | ROLE_USER |
| + 5-10 clients | Generes avec Faker | `User1234!@` | ROLE_USER |

#### MenuFixtures

**Fichier** : `src/DataFixtures/MenuFixtures.php`

Cree **20 menus** aleatoires :
- Nom genere avec Faker + nom du theme
- Prix entre 25 et 150 euros par personne
- 2 a 20 personnes minimum
- 70% ont un stock limite (5-50), 30% stock illimite
- 3 a 6 recettes par menu
- 0 a 3 regimes alimentaires
- Image copiee depuis `fixtures/images/themes/`

#### RecipeFixtures

**Fichier** : `src/DataFixtures/RecipeFixtures.php`

Cree des recettes avec :
- Titre et description generes
- Categorie assignee (Entree, Plat, Fromage ou Dessert)
- 0 a 3 allergenes
- 1 a 3 illustrations copiees depuis `fixtures/images/`

#### OrderFixtures

**Fichier** : `src/DataFixtures/OrderFixtures.php`

Cree **30 commandes** aleatoires avec un statut realiste selon la date de livraison :

| Date de livraison | Statuts possibles |
|---|---|
| Plus de 7 jours dans le passe | COMPLETED (80%) ou CANCELLED (20%) |
| Dans les 7 derniers jours | DELIVERED, WAITING_MATERIAL_RETURN, COMPLETED |
| Dans les 2 prochains jours | READY, DELIVERING, DELIVERED |
| Dans les 7 prochains jours | VALIDATED, PREPARING, READY |
| Plus de 7 jours dans le futur | PENDING, VALIDATED |

Chaque commande a :
- Un client et un menu aleatoires
- Un nombre de personnes entre le min du menu et min+10
- Des frais de livraison calcules selon la distance
- 50% de chance d'avoir un pret de materiel
- Une raison d'annulation si annulee

#### AddressFixtures

**Fichier** : `src/DataFixtures/AddressFixtures.php`

Cree 1 a 3 adresses par utilisateur :
- 60% a Bordeaux (code postal 330XX)
- 40% hors Bordeaux (Merignac, Pessac, Talence, Begles, Arcachon, Libourne)
- La premiere adresse est l'adresse par defaut

#### OrderStatsFixtures

**Fichier** : `src/DataFixtures/OrderStatsFixtures.php`

Copie les donnees des commandes MariaDB vers MongoDB pour avoir des statistiques de test.

### Commandes pour charger les fixtures

```bash
# Charger toutes les fixtures (ATTENTION : efface toutes les donnees !)
php bin/console doctrine:fixtures:load

# Charger sans confirmation
php bin/console doctrine:fixtures:load --no-interaction

# Charger en ajoutant aux donnees existantes (pas de purge)
php bin/console doctrine:fixtures:load --append
```

### Librarie Faker

Les fixtures utilisent **FakerPHP** pour generer des donnees realistes en francais :

```php
$faker = Factory::create('fr_FR');

$faker->firstName();          // "Marie"
$faker->lastName();           // "Dupont"
$faker->email();              // "marie.dupont@example.com"
$faker->phoneNumber();        // "06 12 34 56 78"
$faker->paragraph(3);         // 3 paragraphes de lorem ipsum
$faker->numberBetween(1, 10); // Nombre aleatoire entre 1 et 10
$faker->boolean(80);          // true 80% du temps
$faker->dateTimeBetween('-30 days', '+60 days'); // Date aleatoire
```

---

## Commande CLI : CreateAdminCommand

**Fichier** : `src/Command/CreateAdminCommand.php`

Commande pour creer un administrateur en ligne de commande (utile pour le premier deploiement) :

```bash
php bin/console app:create-admin
```

La commande demande interactivement :
- Email
- Prenom
- Nom
- Mot de passe
- Role (ROLE_ADMIN par defaut)

---

## Resumé des commandes de base de donnees

```bash
# === MariaDB ===

# Creer la base
php bin/console doctrine:database:create

# Executer les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Generer une migration apres modification d'entite
php bin/console make:migration

# Valider que le schema est correct
php bin/console doctrine:schema:validate

# === MongoDB ===
# MongoDB n'a pas de migrations (schema-less)
# Les collections sont creees automatiquement
# Les fixtures MongoDB sont chargees avec les autres fixtures

# === Utilitaire ===
# Creer un admin en CLI
php bin/console app:create-admin
```

---

## Voir aussi

- [ENTITIES.md](ENTITIES.md) - Structure des entites
- [ARCHITECTURE.md](ARCHITECTURE.md) - Vue d'ensemble
- [SERVICES.md](SERVICES.md) - OrderStatsService (MongoDB)
