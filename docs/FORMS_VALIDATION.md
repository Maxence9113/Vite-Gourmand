# Formulaires et Validation

## Comment fonctionnent les formulaires Symfony ?

Dans Symfony, les formulaires sont des **classes PHP** (Form Types) qui decrivent les champs d'un formulaire. Le framework gere automatiquement :
- La generation du HTML
- La validation des donnees
- Le mapping vers les entites

### Cycle de vie d'un formulaire

```
1. Le controleur cree le formulaire
   $form = $this->createForm(MenuType::class, $menu);

2. Le formulaire traite la requete HTTP
   $form->handleRequest($request);

3. Verification : le formulaire a-t-il ete soumis et est-il valide ?
   if ($form->isSubmitted() && $form->isValid()) {
       // Les donnees sont deja mappees sur l'entite $menu
       $entityManager->persist($menu);
       $entityManager->flush();
   }

4. Le template affiche le formulaire
   {{ form_start(form) }}
   {{ form_row(form.name) }}
   {{ form_end(form) }}
```

---

## Liste des formulaires

### OrderType - Formulaire de commande

**Fichier** : `src/Form/OrderType.php`
**Entite** : `Order`

| Champ | Type Symfony | Description |
|---|---|---|
| `numberOfPersons` | IntegerType | Nombre de convives |
| `deliveryDateTime` | DateTimeType | Date et heure de livraison |
| `deliveryAddress` | EntityType (Address) | Selection parmi les adresses |
| `hasMaterialLoan` | CheckboxType | Pret de materiel ? |

**Validation** : Le champ `deliveryDateTime` utilise le validateur personnalise `@ValidDeliveryDateTime`.

---

### MenuType - Formulaire de menu (admin)

**Fichier** : `src/Form/MenuType.php`
**Entite** : `Menu`

| Champ | Type Symfony | Description |
|---|---|---|
| `name` | TextType | Nom du menu |
| `description` | TextareaType | Description |
| `nb_person_min` | IntegerType | Minimum de personnes |
| `price_per_person` | IntegerType | Prix par personne (en centimes) |
| `stock` | IntegerType (nullable) | Stock disponible |
| `illustration` | FileType | Image du menu |
| `textAlt` | TextType | Texte alternatif de l'image |
| `theme` | EntityType (Theme) | Theme du menu |
| `dietetary` | EntityType (Dietetary) | Regimes alimentaires (multiple) |
| `recipes` | EntityType (Recipe) | Recettes du menu (multiple) |

---

### RecipeType - Formulaire de recette (admin)

**Fichier** : `src/Form/RecipeType.php`
**Entite** : `Recipe`

| Champ | Type Symfony | Description |
|---|---|---|
| `title` | TextType | Titre de la recette |
| `description` | TextareaType | Description |
| `category` | EntityType (Category) | Categorie |
| `allergens` | EntityType (Allergen) | Allergenes (multiple) |
| `recipeIllustrations` | CollectionType | Collection d'illustrations |

Le champ `recipeIllustrations` utilise **CollectionType** : c'est un champ qui contient plusieurs sous-formulaires `RecipeIllustrationType`. Cela permet d'ajouter/supprimer dynamiquement des illustrations.

---

### RecipeIllustrationType - Sous-formulaire d'illustration

**Fichier** : `src/Form/RecipeIllustrationType.php`
**Entite** : `RecipeIllustration`

| Champ | Type Symfony | Description |
|---|---|---|
| `imageFile` | FileType | Fichier image a uploader |
| `alt_text` | TextType | Texte alternatif (accessibilite) |

---

### RegisterUserType - Inscription

**Fichier** : `src/Form/RegisterUserType.php`
**Entite** : `User`

| Champ | Type Symfony | Description |
|---|---|---|
| `email` | EmailType | Email (identifiant) |
| `firstname` | TextType | Prenom |
| `lastname` | TextType | Nom |
| `plainPassword` | RepeatedType (PasswordType) | Mot de passe + confirmation |

Le champ `plainPassword` utilise **RepeatedType** : l'utilisateur doit saisir le mot de passe deux fois et les deux doivent correspondre.

---

### PasswordUserType - Changement de mot de passe

**Fichier** : `src/Form/PasswordUserType.php`
**Entite** : `User`

| Champ | Type Symfony | Description |
|---|---|---|
| `currentPassword` | PasswordType | Mot de passe actuel (verification) |
| `plainPassword` | RepeatedType (PasswordType) | Nouveau mot de passe + confirmation |

---

### AddressType - Adresse de livraison

**Fichier** : `src/Form/AddressType.php`
**Entite** : `Address`

| Champ | Type Symfony | Description |
|---|---|---|
| `label` | TextType (nullable) | Libelle ("Domicile", "Travail", ...) |
| `street` | TextType | Rue et numero |
| `postalCode` | TextType | Code postal |
| `city` | TextType | Ville |
| `phone` | TelType | Numero de telephone |
| `isDefault` | CheckboxType | Adresse par defaut ? |

---

### ContactType - Formulaire de contact

**Fichier** : `src/Form/ContactType.php`
**Entite** : Aucune (formulaire libre)

| Champ | Type Symfony | Description |
|---|---|---|
| `senderName` | TextType | Nom du visiteur |
| `senderEmail` | EmailType | Email du visiteur |
| `subject` | TextType | Sujet du message |
| `message` | TextareaType | Corps du message |

Ce formulaire n'est pas lie a une entite : les donnees sont envoyees par email puis supprimees.

---

### ReviewType - Formulaire d'avis

**Fichier** : `src/Form/ReviewType.php`
**Entite** : `Review`

| Champ | Type Symfony | Description |
|---|---|---|
| `rating` | ChoiceType | Note de 1 a 5 |
| `comment` | TextareaType | Commentaire textuel |

---

### OpeningScheduleType et BulkOpeningScheduleType

**Fichiers** : `src/Form/OpeningScheduleType.php`, `src/Form/BulkOpeningScheduleType.php`

| Champ | Type Symfony | Description |
|---|---|---|
| `dayOfWeek` | EnumType (DayOfWeek) | Jour de la semaine |
| `openingTime` | TimeType | Heure d'ouverture |
| `closingTime` | TimeType | Heure de fermeture |
| `isOpen` | CheckboxType | Ouvert ce jour-la ? |

`BulkOpeningScheduleType` contient un **CollectionType** de `OpeningScheduleType` pour modifier tous les jours en une seule fois.

---

### Formulaires CRUD simples

| Formulaire | Fichier | Champs |
|---|---|---|
| CategoryType | `src/Form/CategoryType.php` | `name` |
| AllergenType | `src/Form/AllergenType.php` | `name` |
| DietetaryType | `src/Form/DietetaryType.php` | `name` |
| ThemeType | `src/Form/ThemeType.php` | `name`, `description` |

---

## Validation

### Validation des entites (annotations)

Symfony valide les donnees des formulaires grace aux **contraintes de validation** declarees sur les entites :

```php
// Exemple sur User.php
#[Assert\NotBlank]
#[Assert\Email]
private string $email;

#[Assert\Length(min: 2, max: 100)]
private string $firstname;
```

### Validateur personnalise : ValidDeliveryDateTime

**Fichiers** :
- `src/Validator/ValidDeliveryDateTime.php` (la contrainte)
- `src/Validator/ValidDeliveryDateTimeValidator.php` (la logique)

Ce validateur verifie que la date de livraison :

1. **Est au moins 48h dans le futur** :
   ```php
   $minimumDate = new \DateTimeImmutable('+48 hours');
   if ($value < $minimumDate) {
       // Erreur : "La livraison doit etre reservee au moins 48h a l'avance"
   }
   ```

2. **Tombe pendant les heures d'ouverture** du restaurant :
   ```php
   if (!$this->openingScheduleManager->isValidDeliveryDateTime($value)) {
       // Erreur : "La livraison n'est pas possible a cette date/heure"
   }
   ```

### Theme de formulaire personnalise

**Fichier** : `templates/forms/custom_form_theme.html.twig`

Ce fichier personnalise le rendu HTML de tous les formulaires de l'application. Configure dans `config/packages/twig.yaml` :

```yaml
twig:
    form_themes: ['forms/custom_form_theme.html.twig']
```

Cela permet d'avoir un style uniforme sans repeter du HTML dans chaque template.

---

## Protection CSRF

Tous les formulaires Symfony incluent automatiquement un **token CSRF** (Cross-Site Request Forgery). Ce token empeche les attaques ou un site malveillant enverrait des formulaires a la place de l'utilisateur.

Pour les actions de suppression (qui n'utilisent pas de formulaire Symfony), le token CSRF est verifie manuellement :

```php
// Dans le template
<form method="post" action="{{ path('admin_menu_delete', {id: menu.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ menu.id) }}">
    <button type="submit">Supprimer</button>
</form>

// Dans le controleur
if ($this->isCsrfTokenValid('delete' . $menu->getId(), $request->request->get('_token'))) {
    // Suppression autorisee
}
```

---

## Voir aussi

- [CONTROLLERS_ROUTES.md](CONTROLLERS_ROUTES.md) - Utilisation des formulaires dans les controleurs
- [ORDERS.md](ORDERS.md) - Formulaire de commande en detail
- [TEMPLATES.md](TEMPLATES.md) - Rendu des formulaires dans Twig
