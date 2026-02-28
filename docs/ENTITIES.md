# Modele de donnees - Entites et Relations

## Diagramme des relations

```
User (1) ───────────M──> Order (1) <──1── Review
  │ (1)                     │
  └────M──> Address         │ (snapshot des donnees Menu)
                            │
                            └──> orderNumber, menuName, menuPricePerPerson,
                                 numberOfPersons, deliveryAddress, totalPrice...

Menu (M) <──────1── Theme
  │
  ├──M──> Recipe (M) <──M── Allergen
  │          │ (1)
  │          └──M──> RecipeIllustration
  │
  └──M──> Dietetary

Category (1) ──────M──> Recipe

OpeningSchedule (table independante, 1 ligne par jour)

PasswordResetToken (table independante, liee a User)
```

**Point cle** : Quand une commande est creee, les informations du menu et du client sont **copiees** (snapshot) dans la commande. Ainsi, si le menu change plus tard, la commande conserve ses donnees d'origine.

---

## Entite User

**Fichier** : `src/Entity/User.php`
**Table** : `user`

L'entite User represente un utilisateur de l'application (client, employe ou admin).

### Champs

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant unique |
| `email` | string (180, unique) | Email = identifiant de connexion |
| `password` | string | Mot de passe hashe (bcrypt) |
| `roles` | json (array) | Tableau de roles : `["ROLE_USER"]`, `["ROLE_EMPLOYEE"]`, etc. |
| `firstname` | string (100) | Prenom |
| `lastname` | string (100) | Nom de famille |
| `isEnabled` | bool | Compte actif ou desactive |

### Relations

| Relation | Type | Cible | Description |
|---|---|---|---|
| `orders` | OneToMany | Order | Les commandes de l'utilisateur |
| `addresses` | OneToMany | Address | Les adresses de livraison |

### Implementation de la securite

User implemente deux interfaces de Symfony :
- **`UserInterface`** : Methodes `getRoles()`, `getUserIdentifier()`, `eraseCredentials()`
- **`PasswordAuthenticatedUserInterface`** : Methode `getPassword()`

```php
// L'identifiant de connexion est l'email
public function getUserIdentifier(): string
{
    return (string) $this->email;
}
```

---

## Entite Order

**Fichier** : `src/Entity/Order.php`
**Table** : `order`

L'entite Order represente une commande de traiteur. C'est l'entite la plus complexe de l'application.

### Champs - Identification

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant unique |
| `orderNumber` | string (unique) | Numero au format `ORD-YYYYMMDD-XXXXX` |

### Champs - Informations client (snapshot)

| Champ | Type | Description |
|---|---|---|
| `customerFirstname` | string | Prenom du client au moment de la commande |
| `customerLastname` | string | Nom du client |
| `customerEmail` | string | Email du client |
| `customerPhone` | string | Telephone du client |

### Champs - Livraison

| Champ | Type | Description |
|---|---|---|
| `deliveryAddress` | string | Adresse complete de livraison (texte) |
| `deliveryDateTime` | DateTimeImmutable | Date et heure de livraison souhaitee |
| `deliveryDistanceKm` | int (nullable) | Distance en km depuis le restaurant |
| `deliveryCost` | int | Frais de livraison **en centimes** |

### Champs - Menu (snapshot)

| Champ | Type | Description |
|---|---|---|
| `menuName` | string | Nom du menu commande |
| `menuPricePerPerson` | int | Prix par personne **en centimes** |
| `numberOfPersons` | int | Nombre de convives |
| `menuSubtotal` | int | Sous-total (prix x personnes) **en centimes** |

### Champs - Tarification

| Champ | Type | Description |
|---|---|---|
| `discountAmount` | int (nullable) | Montant de la reduction **en centimes** |
| `totalPrice` | int | Prix total final **en centimes** |

### Champs - Statut et historique

| Champ | Type | Description |
|---|---|---|
| `status` | OrderStatus (enum) | Statut actuel de la commande |
| `statusHistory` | json (array) | Historique complet des changements de statut |
| `cancellationReason` | string (nullable) | Raison d'annulation |

### Champs - Materiel

| Champ | Type | Description |
|---|---|---|
| `hasMaterialLoan` | bool | Le client emprunte-t-il du materiel ? |
| `materialReturnDeadline` | DateTimeImmutable (nullable) | Date limite de retour |
| `materialReturned` | bool (nullable) | Le materiel a-t-il ete retourne ? |

### Champs - Dates

| Champ | Type | Description |
|---|---|---|
| `createdAt` | DateTimeImmutable | Date de creation |
| `updatedAt` | DateTimeImmutable | Derniere modification |
| `acceptedAt` | DateTimeImmutable (nullable) | Date de validation |
| `completedAt` | DateTimeImmutable (nullable) | Date de completion |
| `cancelledAt` | DateTimeImmutable (nullable) | Date d'annulation |

### Relations

| Relation | Type | Cible | Description |
|---|---|---|---|
| `user` | ManyToOne | User | L'utilisateur qui a passe la commande |
| `review` | OneToOne | Review | Avis laisse sur la commande (optionnel) |

### Methodes metier importantes

```php
// Genere un numero unique : ORD-20250125-00001
$order->generateOrderNumber();

// Change le statut ET met a jour l'historique automatiquement
$order->changeStatus(OrderStatus::VALIDATED);
// Cela met aussi a jour acceptedAt, completedAt ou cancelledAt selon le statut

// Calcul de la livraison : 5 euros si Bordeaux, 5 + 0.59/km sinon
$cost = $order->calculateDeliveryCost($isInBordeaux, $distanceKm);

// Sous-total = prix par personne x nombre de personnes
$subtotal = $order->calculateMenuSubtotal();

// Reduction de 10% si 5+ personnes de plus que le minimum du menu
$discount = $order->calculateDiscount($menuMinPersons);

// Prix total = sous-total + livraison - reduction
$total = $order->calculateTotalPrice();

// Initialise avec valeurs par defaut (createdAt, status PENDING, orderNumber)
$order->initialize();
```

### Important : Les prix sont en centimes !

Tous les prix dans l'application sont stockes **en centimes** (int) pour eviter les problemes d'arrondi avec les flottants.

- `5000` = 50,00 euros
- `2500` = 25,00 euros
- `59` = 0,59 euros

Pour afficher en euros dans Twig : `{{ order.totalPrice / 100 | number_format(2, ',', ' ') }} euros`

---

## Entite Menu

**Fichier** : `src/Entity/Menu.php`
**Table** : `menu`

### Champs

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant unique |
| `name` | string | Nom du menu |
| `description` | text | Description detaillee |
| `nb_person_min` | int | Nombre minimum de personnes pour commander |
| `price_per_person` | int | Prix par personne **en centimes** |
| `stock` | int (nullable) | Nombre de portions disponibles (`null` = illimite) |
| `illustration` | string (nullable) | Chemin de l'image (`/uploads/menu_illustrations/...`) |
| `textAlt` | string (nullable) | Texte alternatif de l'image (accessibilite) |

### Relations

| Relation | Type | Cible | Description |
|---|---|---|---|
| `theme` | ManyToOne | Theme | Theme du menu (Noel, Mariage, etc.) |
| `recipes` | ManyToMany | Recipe | Recettes composant le menu |
| `dietetary` | ManyToMany | Dietetary | Regimes alimentaires couverts |

### Methodes

```php
$menu->isAvailable();     // Verifie si le stock n'est pas epuise
$menu->decrementStock();  // Diminue le stock de 1
```

---

## Entite Recipe

**Fichier** : `src/Entity/Recipe.php`
**Table** : `recipe`

### Champs

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant unique |
| `title` | string | Nom de la recette |
| `description` | text | Description de la recette |

### Relations

| Relation | Type | Cible | Description |
|---|---|---|---|
| `category` | ManyToOne | Category | Categorie (Entree, Plat, Fromage, Dessert) |
| `allergens` | ManyToMany | Allergen | Allergenes presents |
| `recipeIllustrations` | OneToMany | RecipeIllustration | Photos de la recette |
| `menus` | ManyToMany (inverse) | Menu | Menus contenant cette recette |

---

## Entite RecipeIllustration

**Fichier** : `src/Entity/RecipeIllustration.php`
**Table** : `recipe_illustration`

### Champs

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant unique |
| `name` | string | Nom du fichier (genere automatiquement) |
| `url` | string | Chemin relatif vers l'image |
| `alt_text` | string (nullable) | Texte alternatif (accessibilite) |
| `imageFile` | File (transient) | Objet fichier temporaire, pas stocke en BDD |

### Relations

| Relation | Type | Cible |
|---|---|---|
| `recipe` | ManyToOne | Recipe |

---

## Entites simples (references)

### Category

**Fichier** : `src/Entity/Category.php` | **Table** : `category`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `name` | string | Nom (ex: "Entree", "Plat", "Fromage", "Dessert") |

Relation : `OneToMany` vers Recipe

### Theme

**Fichier** : `src/Entity/Theme.php` | **Table** : `theme`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `name` | string | Nom (ex: "Noel", "Mariage", "Barbecue") |
| `description` | text (nullable) | Description du theme |

Relation : `OneToMany` vers Menu

### Allergen

**Fichier** : `src/Entity/Allergen.php` | **Table** : `allergen`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `name` | string | Nom (ex: "Gluten", "Lactose", "Arachides") |

Relation : `ManyToMany` avec Recipe

### Dietetary

**Fichier** : `src/Entity/Dietetary.php` | **Table** : `dietetary`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `name` | string | Nom (ex: "Vegan", "Vegetarien", "Sans gluten") |

Relation : `ManyToMany` avec Menu

---

## Entite Address

**Fichier** : `src/Entity/Address.php`
**Table** : `address`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `label` | string (nullable) | Libelle (ex: "Domicile", "Travail") |
| `street` | string | Rue et numero |
| `city` | string | Ville |
| `postalCode` | string | Code postal |
| `phone` | string | Telephone |
| `isDefault` | bool | Adresse par defaut |

Relation : `ManyToOne` vers User

---

## Entite Review

**Fichier** : `src/Entity/Review.php`
**Table** : `review`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `customerName` | string | Nom affiche (genere : "Prenom N.") |
| `rating` | int | Note de 1 a 5 |
| `comment` | text | Commentaire |
| `createdAt` | DateTimeImmutable | Date de creation |
| `isValidated` | bool | Valide par un employe/admin |

Relation : `OneToOne` avec Order (via `orderRef`)

---

## Entite OpeningSchedule

**Fichier** : `src/Entity/OpeningSchedule.php`
**Table** : `opening_schedule`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `dayOfWeek` | DayOfWeek (enum) | Jour de la semaine (lundi=1 a dimanche=7) |
| `openingTime` | Time | Heure d'ouverture |
| `closingTime` | Time | Heure de fermeture |
| `isOpen` | bool | Ouvert ce jour-la ? |
| `createdAt` | DateTimeImmutable | Date de creation |
| `updatedAt` | DateTimeImmutable (nullable) | Derniere modification |

Contrainte d'unicite sur `dayOfWeek` (un seul horaire par jour).

---

## Entite PasswordResetToken

**Fichier** : `src/Entity/PasswordResetToken.php`
**Table** : `password_reset_token`

| Champ | Type | Description |
|---|---|---|
| `id` | int (auto) | Identifiant |
| `token` | string (unique) | Token aleatoire (64 caracteres hex) |
| `expiresAt` | DateTimeImmutable | Date d'expiration |
| `used` | bool | Token deja utilise ? |

Relation : `ManyToOne` vers User

---

## Document MongoDB : OrderStats

**Fichier** : `src/Document/OrderStats.php`
**Collection** : `order_stats`

Ce document stocke une copie simplifiee des donnees de commande pour les graphiques et statistiques du back-office.

| Champ | Type | Description |
|---|---|---|
| `id` | string (auto MongoDB) | ObjectId |
| `orderId` | int | ID de la commande MariaDB correspondante |
| `menuId` | int | ID du menu |
| `menuName` | string | Nom du menu |
| `themeName` | string | Nom du theme |
| `totalPrice` | float | Prix total **en euros** (pas en centimes !) |
| `numberOfPeople` | int | Nombre de personnes |
| `orderDate` | DateTime | Date de la commande |

**Attention** : Contrairement aux entites MariaDB, les prix dans MongoDB sont en **euros** (float), pas en centimes.

---

## Enums PHP

### OrderStatus

**Fichier** : `src/Enum/OrderStatus.php`

Enum backed par une string, represente les etats possibles d'une commande :

| Valeur | Label francais | Description |
|---|---|---|
| `pending` | En attente | Commande vient d'etre creee |
| `validated` | Validee | Acceptee par un employe |
| `preparing` | En preparation | En cours de preparation |
| `ready` | Prete a livrer | Preparation terminee |
| `delivering` | En livraison | En cours de livraison |
| `delivered` | Livree | Arrivee chez le client |
| `waiting_material_return` | En attente retour materiel | Le client doit rendre le materiel |
| `completed` | Terminee | Commande finalisee |
| `cancelled` | Annulee | Commande annulee |

#### Methodes de l'enum

```php
$status->getLabel();        // "En attente"
$status->getBadgeClass();   // "warning" (pour la classe CSS du badge)
$status->getIcon();         // "clock" (icone Feather)
$status->isCancellable();   // true si PENDING, VALIDATED ou PREPARING
$status->isEditable();      // true seulement si PENDING
$status->canReceiveReview();// true seulement si COMPLETED
$status->isFinal();         // true si COMPLETED ou CANCELLED
$status->getNextStatuses(); // Tableau des statuts suivants autorises
```

#### Transitions de statut autorisees

```
PENDING ──────> VALIDATED ──────> PREPARING ──────> READY
   │                │                  │                │
   │                │                  │                v
   │                │                  │           DELIVERING
   │                │                  │                │
   │                │                  │                v
   │                │                  │           DELIVERED
   │                │                  │            │       │
   │                │                  │            v       v
   │                │                  │   WAITING_MATERIAL  COMPLETED
   │                │                  │     _RETURN
   │                │                  │        │
   │                │                  │        v
   └── CANCELLED <──┴── CANCELLED <────┘    COMPLETED
```

### DayOfWeek

**Fichier** : `src/Enum/DayOfWeek.php`

Enum backed par un int (1=lundi a 7=dimanche) :

```php
DayOfWeek::MONDAY->getLabel();      // "Lundi"
DayOfWeek::MONDAY->getShortLabel(); // "Lun"
DayOfWeek::fromDateTime($date);     // Cree depuis un objet DateTime
DayOfWeek::all();                   // Tableau de tous les jours
```

---

## Voir aussi

- [ARCHITECTURE.md](ARCHITECTURE.md) - Vue d'ensemble
- [ORDERS.md](ORDERS.md) - Cycle de vie complet des commandes
- [DATABASE.md](DATABASE.md) - Migrations et fixtures
