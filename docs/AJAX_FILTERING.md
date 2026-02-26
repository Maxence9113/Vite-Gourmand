# Filtrage AJAX des Menus - Documentation

## Vue d'ensemble

Le système de filtrage AJAX permet aux utilisateurs de filtrer les menus en temps réel **sans rechargement de page**.

## Architecture

### Backend - API REST

**Fichier :** `src/Controller/Api/MenuApiController.php`

**Route :** `GET /api/menus/filter`

**Paramètres acceptés :**
- `theme` : ID du thème (int)
- `dietetary[]` : Tableau d'IDs de régimes alimentaires
- `price_max` : Prix maximum en euros (float)
- `nb_person_min` : Nombre minimum de personnes (int)

**Réponse JSON :**
```json
{
  "success": true,
  "menus": [
    {
      "id": 1,
      "name": "Menu Noël",
      "description": "...",
      "illustration": "/uploads/...",
      "pricePerPerson": 5000,
      "nbPersonMin": 10,
      "theme": {
        "id": 1,
        "name": "Noël"
      },
      "dietetaries": [...]
    }
  ],
  "count": 5
}
```

### Frontend - JavaScript AJAX

**Fichier :** `assets/menu-filter-ajax.js`

**Fonctionnement :**

1. **Écoute des événements** sur les filtres (slider prix, select thème, boutons régimes)
2. **Collecte des paramètres** depuis le formulaire
3. **Requête AJAX** vers `/api/menus/filter` avec `fetch()`
4. **Mise à jour du DOM** : génération dynamique des cartes de menus
5. **Gestion du loading** : spinner pendant le chargement

**Événements écoutés :**
- `input` sur le slider de prix (avec debounce 300ms)
- `change` sur le select de thème
- `input` sur le champ nombre de personnes (avec debounce 300ms)
- `click` sur les boutons de régimes alimentaires

### Template Twig

**Fichier :** `templates/menu/catalog.html.twig`

**Modifications :**
- Ajout du block `{% block javascripts %}` pour inclure le JS
- Ajout du CSS pour le loader/spinner
- Les IDs importants : `#filterForm`, `#menuGrid`

## Optimisations

### Debouncing

Pour éviter de faire trop de requêtes pendant que l'utilisateur ajuste le slider de prix ou tape le nombre de personnes, on utilise un **debounce de 300ms**.

Cela signifie que la requête AJAX ne se déclenche que 300ms après que l'utilisateur a arrêté de bouger le slider.

```javascript
// Créer une version debounced
const debouncedFilter = debounce(filterMenus, 300);

// Utiliser sur le slider
priceSlider.addEventListener('input', debouncedFilter);
```

### Eager Loading

Le repository utilise déjà des jointures pour charger les relations en une seule requête SQL :
```php
->leftJoin('m.theme', 't')->addSelect('t')
->leftJoin('m.dietetary', 'd')->addSelect('d')
```

Cela évite le problème N+1 queries.

## Tests

### Test manuel

1. Ouvrir la page `/menus`
2. Ouvrir la console développeur (F12)
3. Changer un filtre (prix, thème, régime)
4. Observer dans la console :
   - `[MenuFilterAjax] Starting filter request...`
   - `[MenuFilterAjax] Filters: ...`
   - `[MenuFilterAjax] Received data: ...`
   - `[MenuFilterAjax] Filter complete. Displayed: X menus`
5. Vérifier que la page ne recharge pas (pas de flash blanc)
6. Vérifier que les cartes de menus se mettent à jour

### Test de l'API directement

```bash
# Tous les menus
curl "https://vitegourmand.maxencedupuis.com/api/menus/filter"

# Filtrer par thème
curl "https://vitegourmand.maxencedupuis.com/api/menus/filter?theme=1"

# Filtrer par prix maximum (30€)
curl "https://vitegourmand.maxencedupuis.com/api/menus/filter?price_max=30"

# Filtrer par régime végétarien (ID 2)
curl "https://vitegourmand.maxencedupuis.com/api/menus/filter?dietetary[]=2"

# Combiner plusieurs filtres
curl "https://vitegourmand.maxencedupuis.com/api/menus/filter?theme=1&price_max=50&nb_person_min=10"
```

## Gestion des erreurs

Le JavaScript gère plusieurs types d'erreurs :

1. **Erreur réseau** : Si l'API ne répond pas
2. **Erreur HTTP** : Si le serveur retourne un code 4xx ou 5xx
3. **Erreur JSON** : Si la réponse n'est pas du JSON valide
4. **Erreur métier** : Si `success: false` dans la réponse

Dans tous les cas, un message d'erreur s'affiche à l'utilisateur.

## Améliorations futures possibles

- [ ] Ajouter une animation de transition lors du changement de grille
- [ ] Gérer l'historique du navigateur (back button) avec `history.pushState()`
- [ ] Sauvegarder les filtres dans `localStorage` pour les restaurer
- [ ] Ajouter un indicateur du nombre de filtres actifs
- [ ] Permettre de réinitialiser tous les filtres en un clic

## Validation cahier des charges

✅ **Exigence :** "Développer la partie dynamique des interfaces utilisateur web ou web mobile"

✅ **Critère :** "Requêtes asynchrones sans rechargement de page"

✅ **Implémentation :**
- API REST en JSON
- Fetch API JavaScript
- Mise à jour dynamique du DOM
- Aucun rechargement de page

## Références

- **MDN Fetch API :** https://developer.mozilla.org/fr/docs/Web/API/Fetch_API
- **Symfony JSON Response :** https://symfony.com/doc/current/components/http_foundation.html#creating-a-json-response
- **Debouncing :** https://www.freecodecamp.org/news/javascript-debounce-example/
