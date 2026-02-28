# Systeme de commandes

## Vue d'ensemble

Le systeme de commandes est le coeur de l'application. Il gere tout le cycle de vie d'une commande de traiteur : de la creation par le client jusqu'a la finalisation (avec eventuel retour de materiel).

## Cycle de vie d'une commande

### Diagramme des statuts

```
  Client cree          Employe          Employe            Employe
  la commande          valide           prepare            prete
       │                  │                │                  │
       v                  v                v                  v
   ┌────────┐       ┌──────────┐     ┌───────────┐     ┌─────────┐
   │PENDING │──────>│VALIDATED │────>│PREPARING  │────>│  READY  │
   └────┬───┘       └────┬─────┘     └─────┬─────┘     └────┬────┘
        │                │                  │                 │
        │                │                  │                 v
        │                │                  │           ┌───────────┐
        │                │                  │           │DELIVERING │
        │                │                  │           └─────┬─────┘
        │                │                  │                 │
        │                │                  │                 v
        │                │                  │           ┌───────────┐
        │                │                  │           │ DELIVERED │
        │                │                  │           └──┬─────┬──┘
        │                │                  │              │     │
        │                │                  │              v     v
        │                │                  │   ┌────────────┐  ┌──────────┐
        │                │                  │   │ WAITING    │  │COMPLETED │
        │                │                  │   │ MATERIAL   │  └──────────┘
        │                │                  │   │ RETURN     │
        │                │                  │   └─────┬──────┘
        │                │                  │         │
        │                │                  │         v
        │                │                  │   ┌──────────┐
        │                │                  │   │COMPLETED │
        │                │                  │   └──────────┘
        │                │                  │
        v                v                  v
   ┌──────────┐    ┌──────────┐      ┌──────────┐
   │CANCELLED │    │CANCELLED │      │CANCELLED │
   └──────────┘    └──────────┘      └──────────┘
```

### Description de chaque statut

| Statut | Qui agit | Ce qui se passe |
|---|---|---|
| **PENDING** | Automatique | La commande vient d'etre creee par le client |
| **VALIDATED** | Employe | L'employe a accepte la commande |
| **PREPARING** | Employe | La cuisine prepare la commande |
| **READY** | Employe | La commande est prete a etre livree |
| **DELIVERING** | Employe | Le livreur est en route |
| **DELIVERED** | Employe | La commande a ete livree au client |
| **WAITING_MATERIAL_RETURN** | Employe | Le client a emprunte du materiel et doit le rendre |
| **COMPLETED** | Employe | Tout est fini (materiel retourne ou pas de materiel) |
| **CANCELLED** | Client ou Employe | La commande a ete annulee |

---

## Creation d'une commande (cote client)

### Prerequis

1. L'utilisateur doit etre **connecte** (ROLE_USER)
2. L'utilisateur doit avoir **au moins une adresse de livraison**
3. Le menu doit etre **disponible** (stock non epuise)

### Etapes dans le formulaire

1. **Choix du menu** : Pre-selectionne depuis la page du menu
2. **Nombre de personnes** : Doit etre >= `menu.nb_person_min`
3. **Date de livraison** : Doit etre dans **au moins 48 heures** et pendant les heures d'ouverture
4. **Adresse de livraison** : Selectionnee parmi les adresses de l'utilisateur
5. **Pret de materiel** : Case a cocher optionnelle

### Calcul du prix

Le prix est calcule automatiquement par `OrderManager::createOrder()` :

```
1. Sous-total = prix_par_personne x nombre_de_personnes
   Exemple : 50,00 euros x 15 personnes = 750,00 euros

2. Frais de livraison :
   - Si adresse dans Bordeaux (code postal 330XX) : 5,00 euros
   - Sinon : 5,00 euros + (0,59 euros x distance_km)
   Exemple : 5,00 + (0,59 x 25 km) = 19,75 euros

3. Reduction (optionnelle) :
   - Si nombre_de_personnes >= nb_person_min + 5 : 10% du sous-total
   Exemple : 15 personnes, min = 8, difference = 7 >= 5 → 10% de 750 = 75,00 euros

4. Total = sous-total + livraison - reduction
   Exemple : 750,00 + 19,75 - 75,00 = 694,75 euros
```

### Validateur personnalise : ValidDeliveryDateTime

**Fichier** : `src/Validator/ValidDeliveryDateTimeValidator.php`

Verifie que la date de livraison :
- Est dans **au moins 48 heures** a partir de maintenant
- Tombe pendant les **heures d'ouverture** du restaurant

```php
#[ValidDeliveryDateTime]
private ?\DateTimeImmutable $deliveryDateTime = null;
```

### Snapshot des donnees

A la creation, les informations suivantes sont **copiees** dans la commande (pas liees par relation) :
- Nom et email du client
- Nom et prix du menu
- Adresse de livraison (en texte)

Pourquoi ? Si le client change son profil ou si le menu est modifie plus tard, la commande conserve les donnees telles qu'elles etaient au moment de la commande.

---

## Gestion des commandes (cote admin)

### Page de liste (`/admin/orders`)

L'employe peut :
- **Filtrer** par statut, recherche textuelle, periode
- **Trier** par date, statut, prix
- Voir les **statistiques rapides** (nombre par statut)

### Changer le statut d'une commande

1. L'employe clique sur un bouton de statut dans la page de detail
2. Le `OrderStatusValidator` verifie si la transition est autorisee
3. Si valide : `$order->changeStatus($newStatus)` est appele
4. L'historique des statuts est mis a jour automatiquement
5. Les dates specifiques sont mises a jour (`acceptedAt`, `completedAt`, etc.)
6. Les stats MongoDB sont mises a jour
7. Un email est envoye au client (pour certains changements)

### Annuler une commande

L'annulation est possible tant que la commande est en `PENDING`, `VALIDATED` ou `PREPARING`.

L'employe doit fournir :
- Une **raison d'annulation** (texte)
- Un **moyen de contact** utilise (telephone ou email)

Le client peut aussi annuler sa commande depuis son espace si elle est encore en `PENDING`.

### Gestion du materiel

Si le client a coche "Pret de materiel" :
1. A la livraison, le statut passe a `DELIVERED`
2. Puis a `WAITING_MATERIAL_RETURN` (si materiel prete)
3. La date limite de retour est fixee a **10 jours apres la livraison**
4. Quand le materiel est retourne, l'employe clique "Marquer comme retourne"
5. Le statut passe automatiquement a `COMPLETED`

Si pas de materiel prete, la commande passe directement de `DELIVERED` a `COMPLETED`.

---

## Historique des statuts

Chaque commande conserve un **historique complet** de tous les changements de statut dans le champ JSON `statusHistory` :

```json
[
    {
        "status": "pending",
        "label": "En attente",
        "changed_at": "2025-01-25 10:30:00"
    },
    {
        "status": "validated",
        "label": "Validee",
        "changed_at": "2025-01-25 14:00:00"
    },
    {
        "status": "preparing",
        "label": "En preparation",
        "changed_at": "2025-01-26 08:00:00"
    }
]
```

Cet historique est mis a jour automatiquement par la methode `Order::changeStatus()`.

---

## Fichiers impliques

| Composant | Fichier | Role |
|---|---|---|
| Entite | `src/Entity/Order.php` | Modele de donnees + methodes metier |
| Enum | `src/Enum/OrderStatus.php` | Statuts + transitions + labels |
| Service | `src/Service/OrderManager.php` | Creation de commande |
| Service | `src/Service/OrderStatusValidator.php` | Validation des transitions |
| Service | `src/Service/OrderStatsService.php` | Stats MongoDB |
| Service | `src/Service/OrderStatisticsService.php` | Stats MariaDB |
| Service | `src/Service/OrderFilterService.php` | Filtrage admin |
| Controleur | `src/Controller/Public/OrderController.php` | Interface client |
| Controleur | `src/Controller/Admin/OrderAdminController.php` | Interface admin |
| Validateur | `src/Validator/ValidDeliveryDateTimeValidator.php` | Regle des 48h |
| Formulaire | `src/Form/OrderType.php` | Formulaire de commande |
| Template | `templates/order/new.html.twig` | Page de creation |
| Template | `templates/admin/orders/show.html.twig` | Detail admin |

---

## Voir aussi

- [ENTITIES.md](ENTITIES.md) - Structure de l'entite Order
- [SERVICES.md](SERVICES.md) - OrderManager, OrderStatusValidator
- [EMAILS.md](EMAILS.md) - Emails lies aux commandes
