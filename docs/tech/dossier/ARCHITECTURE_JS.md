# Architecture JavaScript - Édition Dossier

**Date :** 28 janvier 2026  
**Version :** 1.0  
**Contexte :** Migration Zend 1.12 → Symfony 4.4

---

## 🎯 Vue d'ensemble

L'architecture JavaScript de l'édition de dossier suit une **approche modulaire** basée sur :
- **Modules JavaScript indépendants** (1 fichier = 1 responsabilité)
- **Communication via événements custom** pour découplage
- **Vanilla JavaScript prioritaire** (jQuery uniquement si nécessaire)
- **Pattern IIFE** pour éviter pollution du scope global

**Statistiques :**
- 6 fichiers JavaScript modulaires (~1600 lignes)
- 8 événements custom pour communication inter-modules
- 0 dépendance entre modules (découplage total)

---

## 📂 Structure des fichiers

### `/public/js/dossier/` (6 modules)

```
dossier/
├── form.js                    (770 lignes) - Orchestrateur principal
├── gestion-incomplet.js       (150 lignes) - Documents manquants
├── commission.js              (170 lignes) - Gestion commissions
├── calendar-init.js           (200 lignes) - FullCalendar
├── platau.js                  (224 lignes) - Plat'AU
└── add-collection-widget.js   ( 83 lignes) - CollectionType Symfony

TOTAL : 1597 lignes (vs 2567 lignes legacy monolithique)
```

### `/public/js/common/` (utilitaires transverses)

```
common/
└── add-collection-widget.js  (83 lignes) - Widget Symfony CollectionType
```

---

## 📊 Diagramme de dépendances

```
┌─────────────────────────────────────────────────────────────────┐
│                    edit.html.twig (Template)                    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                 ┌───────────┴───────────┐
                 │   Chargement modules  │
                 └───────────┬───────────┘
                             │
          ┌──────────────────┼──────────────────┐
          │                  │                  │
          ▼                  ▼                  ▼
┌─────────────────┐  ┌──────────────┐  ┌──────────────────┐
│   form.js       │  │commission.js │  │  calendar-init.js│
│ (Orchestrateur) │  │              │  │  (FullCalendar)  │
│                 │  └──────┬───────┘  └────────┬─────────┘
│ • Type/Nature   │         │                   │
│ • Avis/Dérog.   │         │ Émet:             │ Écoute:
│ • Today buttons │         │ calendar:hideAll  │ calendar:hide
│                 │         │                   │ calendar:hideAll
└─────────┬───────┘         │                   │
          │                 └───────────────────┘
          │ Écoute:
          │ dossier:updateAvisVisibility
          │
          ▼
┌──────────────────────┐
│ gestion-incomplet.js │
│ (Documents manquants)│
│                      │
│ Émet:                │
│ dossier:updateAvisVisibility
└──────────────────────┘

┌──────────────────────┐         ┌─────────────────────────┐
│     platau.js        │         │ add-collection-widget.js│
│ (Plat'AU intégration)│         │ (Symfony CollectionType)│
│                      │         │                         │
│ • Pièces jointes     │         │ Émet:                   │
│ • Retry export       │         │ collection:item-added   │
│ • Badges statut      │         │ collection:item-removed │
└──────────────────────┘         └─────────────────────────┘
```

---

## 🔧 Modules détaillés

### 1. form.js (770 lignes) - Orchestrateur principal

**Responsabilité :** Gestion des interactions principales du formulaire

**Fonctionnalités :**
- ✅ Changement Type → Recharge natures + champs conditionnels
- ✅ Changement Nature → Affiche/masque champs
- ✅ Boutons "Aujourd'hui" (dateInsert, dateReception)
- ✅ Avis & Dérogations → Masquage conditionnel
- ✅ Documents urbanisme → Préfixes automatiques

**Événements émis :**
- Aucun (orchestrateur)

**Événements écoutés :**
- `dossier:updateAvisVisibility` → Masque/affiche bloc avis si dossier incomplet

**Pattern :**
```javascript
(function() {
    'use strict';
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        initTypeChangeListener();
        initNatureChangeListener();
        initTodayButtons();
        initAvisDerogationListeners();
        // ...
    });
    
    // Écoute événement custom
    document.addEventListener('dossier:updateAvisVisibility', function(e) {
        const shouldShow = e.detail.shouldShow;
        // Logique affichage/masquage
    });
})();
```

---

### 2. gestion-incomplet.js (150 lignes) - Documents manquants

**Responsabilité :** Gestion dynamique des documents manquants

**Fonctionnalités :**
- ✅ Ajout/suppression documents manquants
- ✅ Boutons "Aujourd'hui" sur dates
- ✅ Émission événement si dossier devient incomplet

**Événements émis :**
- `dossier:updateAvisVisibility` → Notifie form.js de masquer avis si incomplet

**Événements écoutés :**
- Aucun

**Pourquoi événement custom ?**
- `gestion-incomplet.js` ne doit pas connaître `form.js`
- Découplage : on peut supprimer gestion-incomplet.js sans casser form.js
- Extensibilité : d'autres modules peuvent écouter cet événement

**Code :**
```javascript
// Émet événement custom
document.dispatchEvent(new CustomEvent('dossier:updateAvisVisibility', {
    detail: { shouldShow: false }
}));
```

---

### 3. commission.js (170 lignes) - Commissions

**Responsabilité :** Gestion des commissions et affectations

**Fonctionnalités :**
- ✅ Changement commission → Vide affectations
- ✅ Ferme calendriers automatiquement
- ✅ Vide champs dateCommission/dateVisite

**Événements émis :**
- `calendar:hideAll` → Ferme tous les calendriers

**Événements écoutés :**
- Aucun

**Refactoring appliqué (27 janvier 2026) :**
- Avant : 117 lignes avec duplication code calendar-init.js
- Après : 59 lignes (-50%) avec événements custom
- Gain : Code réutilisable + découplage

---

### 4. calendar-init.js (200 lignes) - FullCalendar

**Responsabilité :** Initialisation et gestion FullCalendar v1

**Fonctionnalités :**
- ✅ Initialisation FullCalendar avec options
- ✅ Chargement événements AJAX
- ✅ Sélection date → Remplit champs
- ✅ Boutons Afficher/Masquer calendrier
- ✅ Drag & Drop + Resize événements
- ✅ Dates liées (alerte si multiples)

**Événements émis :**
- Aucun

**Événements écoutés :**
- `calendar:hide` → Ferme calendrier spécifique (commission ou visite)
- `calendar:hideAll` → Ferme tous les calendriers

**Code :**
```javascript
// Écoute événement custom
document.addEventListener('calendar:hide', function(e) {
    const calendarType = e.detail.type; // 'commission' ou 'visite'
    hideCalendar(calendarType);
});

document.addEventListener('calendar:hideAll', function() {
    hideCalendar('commission');
    hideCalendar('visite');
});
```

---

### 5. platau.js (224 lignes) - Plat'AU

**Responsabilité :** Intégration avec plateforme Plat'AU

**Fonctionnalités :**
- ✅ Détection dossier Plat'AU (via `[data-platau-info]`)
- ✅ Affichage conditionnel pièces jointes
- ✅ Retry export PEC/Avis (sans rechargement page)
- ✅ Badges contextuels (warning/info)

**Événements émis :**
- Aucun

**Événements écoutés :**
- `dossier:updateAvisVisibility` → Affiche/masque pièces jointes si avis défini

**Refactoring appliqué (27 janvier 2026) :**
- Remplacement `.toggle('hidden')` par `.remove('hidden')` et `.add('hidden')`
- Gain : Comportement prévisible, -4 lignes nettes

---

### 6. add-collection-widget.js (83 lignes) - CollectionType

**Responsabilité :** Gestion des CollectionType Symfony (ajout/suppression items)

**Fonctionnalités :**
- ✅ Ajout dynamique d'items dans collection
- ✅ Suppression items
- ✅ Réindexation automatique

**Événements émis :**
- `collection:item-added` → Notifie ajout item
- `collection:item-removed` → Notifie suppression item

**Événements écoutés :**
- Aucun

**Usage :**
Utilisé par form.js pour gérer les collections Symfony (documents, prescriptions, etc.)

---

## 🎨 Patterns utilisés

### 1. Custom Events (Communication inter-modules)

**Quand utiliser ?**
- Communication entre 2 fichiers JavaScript différents
- Éviter couplage fort entre modules
- Permettre extensibilité future

**Exemple :**
```javascript
// Module émetteur (gestion-incomplet.js)
document.dispatchEvent(new CustomEvent('dossier:updateAvisVisibility', {
    detail: { shouldShow: false }
}));

// Module récepteur (form.js)
document.addEventListener('dossier:updateAvisVisibility', function(e) {
    const shouldShow = e.detail.shouldShow;
    // Logique métier
});
```

**Avantages :**
- ✅ Découplage total (modules indépendants)
- ✅ Testabilité (mock événements)
- ✅ Extensibilité (nouveaux listeners sans modifier émetteur)

**Inconvénients :**
- ⚠️ Moins explicite que appel direct
- ⚠️ Debugging plus complexe (pas de call stack)

---

### 2. Direct Function Calls (Logique intra-module)

**Quand utiliser ?**
- Logique dans le même fichier JavaScript
- Fonctions utilitaires internes
- Performance critique

**Exemple :**
```javascript
// form.js
function afficherBlocAvis() {
    // Logique affichage
}

function masquerBlocAvis() {
    // Logique masquage
}

// Appel direct dans le même fichier
document.getElementById('checkbox').addEventListener('change', function() {
    if (this.checked) {
        masquerBlocAvis(); // Appel direct
    } else {
        afficherBlocAvis();
    }
});
```

**Avantages :**
- ✅ Explicite et simple
- ✅ Call stack claire (debugging facile)
- ✅ Performance optimale

---

### 3. IIFE (Immediately Invoked Function Expression)

**Objectif :** Éviter pollution du scope global

**Pattern :**
```javascript
(function() {
    'use strict';
    
    // Variables et fonctions privées
    const privateVar = 'value';
    
    function privateFunction() {
        // ...
    }
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        initModule();
    });
})();
```

**Avantages :**
- ✅ Pas de variables globales
- ✅ Évite conflits de noms
- ✅ Encapsulation

---

## 📋 Liste des événements custom

| Événement | Émetteur | Récepteur(s) | Payload | Description |
|-----------|----------|--------------|---------|-------------|
| `dossier:updateAvisVisibility` | gestion-incomplet.js | form.js, platau.js | `{shouldShow: boolean}` | Masque avis si dossier incomplet |
| `calendar:hide` | commission.js | calendar-init.js | `{type: string}` | Ferme calendrier spécifique |
| `calendar:hideAll` | commission.js | calendar-init.js | Aucun | Ferme tous les calendriers |
| `collection:item-added` | add-collection-widget.js | (Tous) | `{index: number}` | Item ajouté dans collection |
| `collection:item-removed` | add-collection-widget.js | (Tous) | `{index: number}` | Item supprimé de collection |

---

## 🛠️ Guide ajout nouvelle interaction

### Scénario 1 : Ajouter logique dans module existant

**Exemple :** Ajouter validation sur champ dans `form.js`

**Étapes :**
1. Ouvrir `form.js`
2. Créer fonction dédiée :
   ```javascript
   function validateField(fieldId) {
       const field = document.getElementById(fieldId);
       // Logique validation
   }
   ```
3. Ajouter listener dans `DOMContentLoaded` :
   ```javascript
   document.getElementById('monChamp').addEventListener('blur', function() {
       validateField('monChamp');
   });
   ```
4. Tester manuellement
5. Commiter

---

### Scénario 2 : Créer nouveau module JavaScript

**Exemple :** Ajouter gestion des contacts dynamiques

**Étapes :**
1. Créer `/public/js/dossier/contacts.js`
2. Structure IIFE :
   ```javascript
   (function() {
       'use strict';
       
       document.addEventListener('DOMContentLoaded', function() {
           initContactsModule();
       });
       
       function initContactsModule() {
           // Logique
       }
   })();
   ```
3. Charger dans template Twig :
   ```twig
   {% block javascripts %}
       {{ parent() }}
       <script src="{{ asset('js/dossier/contacts.js') }}"></script>
   {% endblock %}
   ```
4. Si communication nécessaire → Utiliser événements custom
5. Documenter dans cette page

---

### Scénario 3 : Ajouter événement custom

**Exemple :** Notifier quand validation formulaire OK

**Étapes :**
1. **Émetteur** (validation.js) :
   ```javascript
   document.dispatchEvent(new CustomEvent('dossier:validationSuccess', {
       detail: { formData: data }
   }));
   ```

2. **Récepteur** (autre-module.js) :
   ```javascript
   document.addEventListener('dossier:validationSuccess', function(e) {
       const formData = e.detail.formData;
       // Réagir à l'événement
   });
   ```

3. **Documenter** dans tableau événements ci-dessus

---

## 🚀 Critères de qualité

### ✅ Code Review Checklist

Avant de merger du code JavaScript :

- [ ] **IIFE** : Fonction wrappée pour éviter scope global
- [ ] **Strict mode** : `'use strict';` en première ligne
- [ ] **DOMContentLoaded** : Initialisation après chargement DOM
- [ ] **Nommage** : Variables/fonctions en camelCase explicites
- [ ] **Événements custom** : Utilisés pour communication inter-modules
- [ ] **Pas de jQuery** : Sauf si nécessaire (FullCalendar, plugins legacy)
- [ ] **Commentaires** : Sections commentées (// ===== SECTION =====)
- [ ] **Console** : 0 erreur, 0 warning en navigateur
- [ ] **Tests manuels** : Toutes les interactions testées
- [ ] **Documentation** : Mise à jour ARCHITECTURE_JS.md si nouvelle feature

---

## 📚 Références

### Documentation externe
- [CustomEvent API (MDN)](https://developer.mozilla.org/fr/docs/Web/API/CustomEvent)
- [FullCalendar v1 (legacy)](https://fullcalendar.io/docs/v1/)
- [Symfony CollectionType](https://symfony.com/doc/4.4/form/form_collections.html)

### Documentation interne
- `PLAN_MIGRATION_JS_DOSSIER.md` - Plan global migration
- `REPRENDRE_ICI.md` - État actuel migration
- `MAPPING_LEGACY_REFERENCE.md` - Correspondances Zend/Symfony

---

## 📊 Métriques

### État actuel (28 janvier 2026)

**Taille code :**
- Legacy monolithique : 2567 lignes (1 fichier)
- Migré modulaire : 1597 lignes (6 fichiers)
- Réduction : **-38% lignes**

**Complexité :**
- Legacy : Fonction monolithique 2567 lignes
- Migré : 6 modules moyens 266 lignes/module
- Gain maintenabilité : **+80%** (estimation)

**Migration JavaScript :**
- ✅ Blocs 1-4, 6-8 : 100% complets
- ⏸️ Bloc 5 (Validation) : Reporté (dépend POST-Redirect-GET)
- ⏸️ Bloc 9 (Modals) : Reporté (dépend sauvegarde backend)
- 🟡 Bloc 10 (Cleanup) : **En cours**

**Total :** ~70% migration JavaScript complète

---

## 🎯 Évolutions futures

### Court terme (Bloc 10)
- ✅ Supprimer fichiers legacy obsolètes
- ✅ Documenter architecture (ce fichier)
- ⏳ Tests de non-régression exhaustifs
- ⏳ Audit performance

### Moyen terme (Bloc 5)
- Migration POST-Redirect-GET
- Validation formulaire côté serveur
- Flash messages Symfony
- Routes backend manquantes

### Long terme (après migration complète)
- Refactoring modulaire si form.js > 1200 lignes
- Migration jQuery → Vanilla JS (si besoin)
- Tests automatisés JavaScript (Jest/Cypress)
- Optimisation performance (lazy load, debounce)

---

**Version :** 1.0  
**Dernière mise à jour :** 28 janvier 2026 - 16h45  
**Auteurs :** Migration Prevarisc Team  
**Statut :** ✅ Documentation complète
