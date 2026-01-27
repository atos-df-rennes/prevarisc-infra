# Plan de migration des interactions JavaScript - Formulaire dossier

**Contexte :** Migration des interactions JavaScript du legacy `index.phtml` (2567 lignes, ~40 event listeners, ~16 appels AJAX) vers Symfony 4.4 avec séparation propre en modules JS.

**Périmètre :** Focus sur `prevarisc/application/views/scripts/dossier/index.phtml` uniquement.

**Dernière mise à jour :** 27 janvier 2026 - 10h55

---

## 🎉 Avancement global

**Phase 0 :** ✅ Inventaire et préparation (100%)  
**Bloc 1 :** ✅ Visibilité TYPE/NATURE (100%) - Tests validés ✅  
**Bloc 2 :** ✅ Autocomplete AJAX (100%) - Tests validés ✅  
**Bloc 3 :** ✅ Calendrier (100%) - Tests validés ✅  
**Autres blocs :** En attente

**Total :** ~35% du travail global terminé et validé

**Commits clés :**
- `86b5c60` (27 jan) - feat(dossier): complète intégration calendrier avec événements custom (Bloc 3)
- `55b054f` (26 jan) - feat(dossier): ajout autocomplete AJAX pour préventionnistes (Bloc 2)
- `845deaf` (26 jan) - fix(dossier): corrige affichage facteur de criticité et échéancier de travaux
- `eb2b73d` (26 jan) - feat(dossier): masquage dynamique de l'avis lors du clic sur Incomplet (Tâche 1.3)

---

## 📊 État des lieux

### Legacy (Zend)
- **Fichier :** `prevarisc/application/views/scripts/dossier/index.phtml`
- **Taille :** 2567 lignes de HTML + JavaScript inline mélangés
- **Technologies :** jQuery 1.x, Bootstrap 2, FullCalendar v1, TinyMCE
- **Architecture :** Tout dans un seul fichier (logique, templates, événements)
- **Problèmes :** 
  - Couplage fort HTML/JS
  - Pas de séparation des responsabilités
  - Difficile à maintenir et tester
  - Nombreux `$.live()` (deprecated)

### Migré (Symfony)
- **Template :** `prevarisc-migration/templates/dossier/edit.html.twig`
- **JS existants :** 
  - ✅ `form.js` - Visibilité conditionnelle, TomSelect, dates
  - ✅ `calendar-init.js` - FullCalendar moderne
  - ✅ `gestion-incomplet.js` - Documents manquants
  - ✅ `commission.js` - Sélection commission
  - ✅ `platau.js` - Intégration Plat'AU
  - ✅ `avisDerogation.js` - Avis/Dérogations
  - ⚠️ `dossierGeneral.js` - Legacy datepicker (à supprimer)
  - ⚠️ `dossierDocConsulte.js` - Legacy modal (à moderniser)

---

## 🎯 Objectifs

1. **Migrer 100%** des interactions JavaScript du legacy vers Symfony
2. **Séparer proprement** en modules thématiques (1 fichier = 1 responsabilité)
3. **Moderniser** : 
   - Vanilla JS ou jQuery moderne (pas de `$.live()`)
   - Délégation d'événements
   - Modules IIFE avec strict mode
4. **Conserver l'UX** : Iso-fonctionnalité absolue avec le legacy
5. **Documenter** : Commentaires clairs, JSDoc si nécessaire

---

## 📋 Workplan

### Phase 0 : Préparation et inventaire détaillé
- [x] **0.1** Extraire et documenter toutes les interactions du legacy `index.phtml`
  - Créer un fichier d'inventaire `LEGACY_JS_INVENTORY.md` dans `docs/tech/dossier/`
  - Lister chaque event listener avec son numéro de ligne
  - Catégoriser par bloc thématique
  - Identifier les dépendances inter-blocs
  
- [x] **0.2** Identifier les interactions déjà migrées
  - Marquer dans l'inventaire ce qui existe déjà dans `form.js`, `commission.js`, etc.
  - Identifier les doublons potentiels
  - Lister les écarts fonctionnels (si legacy fait plus que migré)

- [x] **0.3** Préparer l'environnement de test
  - ✅ Documenter comment tester chaque interaction (`GUIDE_TESTS_INTERACTIONS_JS.md`)
  - ✅ Créer des fixtures SQL pour couvrir 21 cas représentatifs (types 1-7, natures 1-66)
  - ✅ Préparer script shell automatisé (`prepare-test-env.sh`)
  - ✅ Créer template résultats de tests (`TEST_RESULTS.md`)

---

### 🟢 Bloc 1 : Visibilité conditionnelle des champs (Type & Nature) ✅ TERMINÉ

**Complexité :** 🔴 Élevée (cœur métier, 68 natures différentes)

**Statut :** ✅ 100% migré, testé et validé (26 janvier 2026)

**Tests manuels :** ✅ Validés par l'utilisateur (26 janvier 2026)
- ✅ Changement type → chargement dynamique natures
- ✅ Changement nature → affichage/masquage champs corrects
- ✅ Clic "Incomplet" → masque l'avis (Tâche 1.3)
- ✅ Retour "Complet" → réaffiche l'avis
- ✅ Facteur de criticité : Type 1 Nature 19 + Type 2/3
- ✅ Échéancier travaux : Type 2/3 uniquement (pas Nature 19)
- ✅ Aucune erreur console JavaScript

**Legacy :**
- Ligne ~242 : `$("#TYPE_DOSSIER").change()` - Change type → masque/affiche sections
- Ligne ~290-360 : Logique `incomplet` → masque avis/commission/calendrier
- Fonctions custom : `$.fn.recupNatures()`, `$.fn.cacherNatureDuSelect()`, `$.fn.afficheChamps()`
- AJAX `/dossier/shownature` et `/dossier/showchamps`

**Fichier cible :** `public/js/dossier/form.js` ✅ Complété

**Implémentation réalisée :**

**Backend Symfony :**
- ✅ `DossierNatureListeRepository::findByTypeOrderedByOrdre(DossierType $type)` - Requête Doctrine
- ✅ Route API `/api/dossier/natures-by-type/{typeId}` (JSON) - Note : route supprimée ensuite, logique intégrée différemment
- ✅ Tests unitaires : `DossierNatureListeRepositoryTest` (3 tests)

**Frontend JavaScript :**
- ✅ `onTypeChange()` - Gestion changement de type avec AJAX
- ✅ `loadNaturesForType()` - Chargement dynamique des natures selon type
- ✅ `updateNatureSelect()` - Mise à jour du select avec nouvelles options
- ✅ `applyTypeSpecificVisibility()` - Logique de visibilité selon type (documenté)
- ✅ `onNatureChange()` - Gestion changement de nature
- ✅ `updateFieldsVisibility()` - Affichage/masquage champs selon nature
- ✅ Configuration `fieldsConfig` - Chargée depuis backend (DossierFieldsService)

**Tâches réalisées :**
- [x] **1.1** Compléter la gestion du changement de TYPE
  - ✅ Tous les cas couverts (types 1-7)
  - ✅ Chargement dynamique des natures via AJAX
  - ✅ Vidage de la sélection de nature au changement de type
  
- [x] **1.2** Compléter la gestion du changement de NATURE
  - ✅ `fieldsConfig` géré côté backend (DossierFieldsService)
  - ✅ Affichage/masquage dynamique des champs selon nature
  - ✅ Ordre d'affichage respecté (géré backend)
  
- [x] **1.3** Gérer l'interdépendance INCOMPLET ↔ AVIS/COMMISSION
  - ✅ Événement personnalisé `dossier:updateAvisVisibility`
  - ✅ Communication inter-modules (gestion-incomplet.js ↔ form.js)
  - ✅ Masquage automatique de l'avis lors du clic "Incomplet"
  - ✅ Réaffichage lors du retour à "Complet"
  
- [x] **1.4** Tests et validation
  - ✅ PHPStan niveau 10 : 0 erreur
  - ✅ Code Style PSR-12 : 0 erreur
  - ✅ Tests unitaires : 234 passent (dont 3 DossierNatureListeRepository)
  - ✅ Tests manuels navigateur : Validés par l'utilisateur (26 janvier 2026)

**Validation technique :**
- ✅ PHPStan niveau 10 : 0 erreur
- ✅ Code Style PSR-12 : 0 erreur
- ✅ Tests unitaires : 234 passent
- ✅ Tests fonctionnels navigateur : Validés ✅

**Commits :**
- `eb2b73d` - feat(dossier): masquage dynamique de l'avis lors du clic sur Incomplet (Tâche 1.3)
- `845deaf` - fix(dossier): corrige affichage facteur de criticité et échéancier de travaux
- `8f5dd1e` - feat(dossier): complète visibilité conditionnelle TYPE/NATURE (Bloc 1)

**Documentation :**
- ✅ `MIGRATION_GAPS_ANALYSIS.md` - Bloc 11 marqué 100% migré
- ✅ Plan session : `/home/dev/.copilot/session-state/88650d26-c984-4ffb-afca-095c27848b36/plan.md`

**Note importante :** 
La route API `/api/dossier/natures-by-type/{typeId}` a été créée puis supprimée.
La logique de chargement des natures a été intégrée différemment dans l'initialisation
du formulaire (commit `7d5e135` - fix: récupère les natures disponibles pour le type sélectionné).

---

### 🟢 Bloc 2 : Auto-complétion et recherches asynchrones ✅ TERMINÉ

**Complexité :** 🟡 Moyenne (APIs REST, TomSelect)

**Statut :** ✅ 100% migré, testé et validé (26 janvier 2026)

**Tests manuels :** ✅ Validés par l'utilisateur (26 janvier 2026)
- ✅ Recherche préventionnistes (min 3 caractères)
- ✅ Requête AJAX vers `/api/utilisateurs/search`
- ✅ Suggestions affichées correctement
- ✅ Sélection multiple fonctionnelle
- ✅ Suppression avec bouton croix
- ✅ En édition : préventionnistes préchargés (uniquement sélectionnés)
- ✅ Performance : < 500ms
- ✅ Aucune erreur console

**Legacy :**
- Ligne ~81-130 : `#preventionnistes-autocomplete` (typeahead)
- Ligne ~130-148 : `#servInstVille` (jQuery autocomplete ville)
- AJAX `/api/1.0/search/users` et route ville

**Fichier cible :** `public/js/dossier/form.js` ✅ Complété

**Implémentation réalisée :**

**Backend Symfony :**
- ✅ `UtilisateurController` API - Route `GET /api/utilisateurs/search`
- ✅ `UtilisateurRepository::searchPreventionnistes()` - Recherche par nom/prénom
- ✅ Retour JSON : `[{id, nom, prenom, displayLabel}]`
- ✅ Minimum 3 caractères, limite 100 résultats

**Frontend JavaScript :**
- ✅ TomSelect avec autocomplete AJAX sur `#dossier_preventionnistes`
- ✅ Configuration : `valueField: 'value'`, `labelField: 'text'`
- ✅ Transformation réponse : `{id, displayLabel}` → `{value, text}`
- ✅ Plugin `remove_button` pour multiselect

**FormType optimisé :**
- ✅ Utilisation de `choices` au lieu de `query_builder`
- ✅ Création : `choices = []` (aucune option préchargée)
- ✅ Édition : `choices = $dossier->getPreventionnistes()->toArray()` (uniquement sélectionnés)
- ✅ Performance : évite de charger 50+ options inutiles (-10 KB HTML)

**Tâches réalisées :**
- [x] **2.1** Créer l'API de recherche utilisateurs
  - ✅ Route REST GET `/api/utilisateurs/search?q={query}&limit={limit}`
  - ✅ Recherche intelligente (nom/prénom, insensible casse)
  - ✅ Filtre sur fonction "Préventionniste"
  
- [x] **2.2** Migrer autocomplete préventionnistes
  - ✅ Remplacé typeahead par TomSelect moderne
  - ✅ AJAX vers nouvelle API Symfony
  - ✅ Multiselect avec suppression
  
- [x] **2.3** Optimiser FormType
  - ✅ Pas de préchargement massif (50+ options → 0-2 options)
  - ✅ Syntaxe propre avec `choices`
  - ✅ Compatible création ET édition
  
- [x] **2.4** Tests et validation
  - ✅ PHPStan niveau 10 : 0 erreur
  - ✅ Code Style PSR-12 : 0 erreur
  - ✅ Tests unitaires : 234 passent
  - ✅ Tests manuels navigateur : Validés par l'utilisateur

**Validation technique :**
- ✅ PHPStan niveau 10 : 0 erreur
- ✅ Code Style PSR-12 : 0 erreur
- ✅ Tests unitaires : 234 passent
- ✅ Tests fonctionnels navigateur : Validés ✅
- ✅ Performance : -10 KB HTML, < 500ms AJAX

**Commits :**
- `55b054f` - feat(dossier): ajout autocomplete AJAX pour préventionnistes (Bloc 2)
- `244f3d7` - chore: suppression de code inutile

**Documentation :**
- ✅ `UtilisateurController` bien documenté avec référence legacy
- ✅ Code JavaScript commenté et explicite

**Note importante :**
L'autocomplete du service instructeur (`#servInstVille`) était déjà migré avec TomSelect
et fonctionnait correctement. Cette tâche s'est concentrée sur les préventionnistes.

---

### ✅ Bloc 3 : Calendrier commissions & visites - TERMINÉ (100%)

**Complexité :** 🔴 Élevée (FullCalendar, logique métier complexe)

**Statut :** ✅ 100% migré, testé et validé (27 janvier 2026)

**Legacy :**
- Ligne ~194-240 : Boutons `.showCalendar` / `.hideCalendar`
- Ligne ~400-850 : Plugin `$.fn.afficheCalendar()` (~450 lignes)
- AJAX `/calendrier-des-commissions/recupevenement`, `/adddates`, `/dialogcomm`
- Gestion drag & drop, resize, sélection de plages

**Fichier cible :** `public/js/dossier/calendar-init.js` + `commission.js`

**Tâches réalisées :**
- [x] **3.1** Vérification conformité calendrier COMMISSION
  - Affichage/masquage au clic bouton ✅
  - Chargement événements depuis routes Symfony ✅
  - Sélection date → remplir `dateCommission` + masquer calendrier ✅
  
- [x] **3.2** Vérification conformité calendrier VISITE
  - Même logique que commission ✅
  - Dates liées affichées correctement ✅
  - Drag & drop fonctionnel ✅
  
- [x] **3.3** Intégration avec champ COMMISSION ⭐
  - Changement commission → vider dates + affectations ✅
  - Masquage automatique calendriers via événement custom `calendar:hideAll` ✅
  - Refactorisation : commission.js 117 → 59 lignes (-50%) ✅
  
- [x] **3.4** Modal création date
  - Formulaire avec périodicité ✅
  - Soumission AJAX fonctionnelle ✅
  
- [x] **3.5** Tests exhaustifs
  - Tous types de dossier (1, 2, 3) ✅
  - Drag & drop événements ✅
  - Resize événements (bloqué en vue mois) ✅
  - Changement commission ✅

**Innovation :** Pattern événements custom
- Événement `calendar:hideAll` pour communication inter-modules
- Découplage commission.js ↔ calendar-init.js
- Cohérent avec `dossier:updateAvisVisibility` (Bloc 1)

**Validation :**
- ✅ Routes backend Symfony (5/5 présentes)
- ✅ Format dates compatible (DateCommissionTransformer)
- ✅ Calendrier s'affiche/masque correctement
- ✅ Dates sélectionnées persistent en base
- ✅ Tests manuels complets : PASS
- ✅ Tests types dossier 1, 2, 3 : PASS

**Commits :**
- `86b5c60` - feat(dossier): complète intégration calendrier avec événements custom (Bloc 3)

---

### ✅ Bloc 4 : Documents d'urbanisme (ajout/suppression dynamique) - TERMINÉ

**Complexité :** 🟢 Faible (CRUD simple, pas d'AJAX)

**Legacy :**
- Ligne ~846-912 : `.suppression` → supprime un doc urbanisme
- Ligne ~1064-1067 : Plugin jQuery `gestionSuppNature()`
- Ligne ~1574-1592 : HTML avec `#listeDocUrba`, bouton `#ajoutDocUrba`

**Implémentation :** ✅ 100% migré (lors phases précédentes)

**Fichiers :**
- ✅ `templates/dossier/edit.html.twig` - CollectionType Symfony (lignes 103-132)
- ✅ `public/js/formulaire/add-collection-widget.js` - Gestionnaire générique (83 lignes)
- ✅ `src/Form/Type/DossierType.php` - Configuration backend

**Tâches :**
- [x] **4.1** ~~Vérifier gestion existante~~
  - ✅ Gestionnaire générique `add-collection-widget.js` en place
  - ✅ CollectionType Symfony avec `allow_add`/`allow_delete`
  
- [x] **4.2** ~~Migrer bouton suppression~~
  - ✅ Bouton icône poubelle avec délégation événements
  - ✅ Événement custom `collection:item-added`
  
- [x] **4.3** ~~Gérer l'ajout dynamique~~
  - ✅ Bouton "Ajouter un numéro" avec `data-prototype`
  - ✅ Clone automatique du prototype Symfony
  
- [x] **4.4** Tester workflow complet
  - ✅ Tests manuels : 10/10 PASS (27 janvier 2026)
  - ✅ Préfixes automatiques fonctionnels ⭐
  - ✅ Persistance BDD validée

**Validation :**
- ✅ Aucun doublon en base après ajout/suppression
- ✅ Numéros de préfixe corrects et automatiques
- ✅ Interface réactive (suppression instantanée)

---

### 🟡 Bloc 5 : Validation et sauvegarde formulaire

**Complexité :** 🟡 Moyenne (logique métier + AJAX)

**Legacy :**
- Ligne ~915-1038 : `.today` → remplir date du jour
- Ligne ~1039-1084 : `#modificationDossier` → AJAX save avec validation
- Fonction `verifDossier()` : validation multi-champs côté client
- AJAX `/dossier/save` ou `/dossier/savenew`

**Fichier cible :** `public/js/dossier/validation.js` (nouveau fichier)

**Tâches :**
- [ ] **5.1** Créer le module `validation.js`
  - Fonction `validateDossier()` : règles métier
  - Validation champs obligatoires selon nature
  - Validation dates (cohérence dateVisite < dateCommission, etc.)
  - Validation longueurs (objet, etc.)
  
- [ ] **5.2** Migrer boutons "Aujourd'hui"
  - Vérifier si déjà dans `form.js` (lignes ~X-Y)
  - Compléter pour tous les champs date manquants
  
- [ ] **5.3** ✅ Soumission POST classique Symfony (DÉCISION VALIDÉE)
  - Utiliser formulaire Symfony standard avec handleRequest()
  - Pas de reload car séparation affichage/édition
  - Validation côté serveur uniquement
  
- [ ] **5.4** Afficher les erreurs de validation
  - Symfony Flash messages pour POST classique
  - Ou affichage inline si AJAX (style Bootstrap)
  
- [ ] **5.5** Tester scénarios de validation
  - Champs obligatoires vides → erreur
  - Dates incohérentes → erreur
  - Sauvegarde réussie → redirection + message succès

**Validation :**
- ✅ Aucune donnée invalide en base
- ✅ Messages d'erreur clairs et localisés
- ✅ Pas de perte de données en cas d'erreur

---

### 🟡 Bloc 6 : Avis & Dérogations (affichage conditionnel)

**⚠️ CLARIFICATION :** `avisDerogation.js` concerne un autre template (modal séparé), pas le formulaire d'édition principal

**Complexité :** 🟡 Moyenne (logique métier + modal)

**Legacy :**
- Ligne ~359-398 : `.hideavis` → masque section avis si incomplet
- Logique conditionnelle : afficher avis uniquement si commission définie
- Avis défavorable (ID=2) → afficher facteur dangérosité + échéancier

**Fichier cible :** `public/js/dossier/form.js (section avis dans formulaire principal)`

**Tâches :**
- [ ] **6.1** Implémenter gestion AVIS dans form.js
  - Pas de réutilisation de avisDerogation.js (contexte différent)
  - Affichage conditionnel des champs avis selon état dossier
  
- [ ] **6.2** Compléter gestion AVIS DÉFAVORABLE
  - Si `avisCommission` = 2 (Défavorable) → afficher champs supplémentaires
  - Champs concernés : `facteurDangerosite`, `echeancierTravaux`
  - Masquer si autre avis sélectionné
  
- [ ] **6.3** Gérer interdépendance INCOMPLET ↔ AVIS
  - Relier avec `gestion-incomplet.js`
  - Si incomplet = true → masquer section avis (classe `hideavis`)
  - Si retour complet → réafficher avis
  
- [ ] **6.4** Tester tous les avis possibles
  - Favorable → champs facteur/échéancier masqués
  - Défavorable → champs affichés + requis
  - Aucun avis → section masquée
  - Avec/sans incomplet → comportement correct

**Validation :**
- ✅ Affichage conditionnel correct pour chaque avis
- ✅ Pas de champ obligatoire manquant si avis défavorable
- ✅ Synchronisation avec état incomplet

---

### ✅ Bloc 7 : Boutons "Aujourd'hui" pour tous les champs date - TERMINÉ

**Complexité :** 🟢 Faible (répétitif mais simple)

**Legacy :**
- Ligne ~915-938 : `$(".today").live('click')` → remplir champ date adjacent

**Implémentation :** ✅ 100% migré (lors phases précédentes)

**Fichiers :**
- ✅ `templates/components/date_field_with_today.html.twig` - Composant réutilisable
- ✅ `public/js/dossier/form.js` - fonction `initTodayButtons()` (lignes 569-591)
- ✅ `public/js/dossier/gestion-incomplet.js` - boutons documents manquants

**Tâches :**
- [x] **7.1** Vérifier implémentation existante
  - ✅ Composant Twig créé et utilisé 13 fois
  - ✅ 14 champs date couverts + 2 dynamiques (documents manquants)
  
- [x] **7.2** ~~Compléter pour tous les champs date manquants~~
  - ✅ Tous les champs couverts
  - ✅ Délégation d'événements moderne (pas `.live()`)
  
- [x] **7.3** Tester avec tous les types de dossier
  - ✅ Tests manuels : 7/7 PASS (27 janvier 2026)
  - ✅ Format YYYY-MM-DD validé tous navigateurs
  - ✅ Dates conditionnelles : comportement correct

**Corrections apportées :**
- 🐛 Fix variable booléenne : `show_today ?? true` au lieu de `|default(true)`
- 🐛 Fix largeur sélecteur année calendrier : 70px → 80px (Chrome)

**Validation :**
- ✅ Tous les champs date ont leur bouton "Aujourd'hui"
- ✅ Format date cohérent (YYYY-MM-DD)
- ✅ Boutons masqués si champ date masqué
- ✅ Pas d'erreur console
- ✅ Compatible Chrome + Firefox

---

### 🔴 Bloc 8 : Intégrations Plat'AU (conditions complexes)

**Complexité :** 🔴 Élevée (multiples conditions, événements namespaced)

**Legacy :**
- Ligne ~1086-1112 : Event listeners avec namespace `.platau`
- `.incomplet.live('click.platau')` → afficher champs Plat'AU si incomplet
- `#AVIS_DOSSIER_COMMISSION.live('change.platau')` → afficher si avis défini
- Fonction `isDossierPlatau()`, `gestionAffichageInfosPlatau()`

**Fichier cible :** `public/js/dossier/platau.js` (déjà existant)

**Tâches :**
- [ ] **8.1** Vérifier conformité avec legacy
  - Relire `platau.js` actuel (récemment modifié)
  - Comparer avec logique legacy lignes 1086-1112
  
- [ ] **8.2** Compléter gestion affichage bloc pièces jointes Plat'AU
  - Afficher SI :
    - Dossier incomplet (état = 1) OU
    - Avis commission défini (value != 0 && != '')
  - Masquer sinon
  
- [ ] **8.3** Gérer badge alerte Plat'AU
  - Badge `#badge-alerte-platau` doit apparaître si pièces jointes manquantes
  - Clic sur badge → scroll vers champ `#field-piecesJointesPlatau`
  
- [ ] **8.4** Synchroniser avec incomplet & avis
  - Relier avec `gestion-incomplet.js` et `avisDerogation.js`
  - Tester toutes les combinaisons :
    - Complet + Aucun avis → masqué
    - Complet + Avis défini → affiché
    - Incomplet + Aucun avis → affiché
    - Incomplet + Avis défini → affiché
  
- [ ] **8.5** ~~Tester retry export~~ ⚠️ NON APPLICABLE
  - **CLARIFICATION :** Boutons #retry-export-pec et #retry-export-avis NON présents dans HTML migré
  - **Raison :** Séparation affichage/édition (reload = perte données)
  - Ces boutons sont sur page affichage (show), pas édition (edit)
  - Code existant dans platau.js peut être conservé pour compatibilité future

**Validation :**
- ✅ Logique d'affichage 100% conforme legacy
- ✅ Badge alerte apparaît uniquement si nécessaire
- ✅ Tests avec dossiers Plat'AU réels (fixture)
- ✅ Tests avec dossiers NON Plat'AU (pas d'interférence)

---

### 🟢 Bloc 9 : Alertes et modals (TinyMCE, confirmations)

**Complexité :** 🟢 Faible (modals Bootstrap standard)

**Legacy :**
- Ligne ~3-70 : `.alerte-link` → modal TinyMCE pour envoi mail alerte
- Ligne ~73-77 : `#suppressionDossier` → modal confirmation suppression
- AJAX `/changement/alerteform` et `/changement/sendmailalerte`

**Fichier cible :** `public/js/dossier/modals.js` (nouveau fichier)

**Tâches :**
- [ ] **9.1** Créer le module `modals.js`
  - Gérer modal confirmation suppression (Bootstrap modal)
  - Gérer modal alerte avec TinyMCE
  
- [ ] **9.2** Migrer modal suppression dossier
  - Bouton "Supprimer le dossier" → ouvrir modal
  - Confirmation → POST vers `/dossier/delete/{id}`
  - Annulation → fermer modal
  
- [ ] **9.3** ⚠️ Migrer modal alerte établissement
  - **CLARIFICATION :** Fonctionnalité ENCORE UTILISÉE
  - ❌ Routes backend /changement/alerteform et /changement/sendmailalerte NON MIGRÉES en Symfony
  - **Action requise :** Créer routes + controllers Symfony AVANT migration JS
  - **Tâche bloquée** en attendant backend
  
- [ ] **9.4** Tester workflows modals
  - Suppression : confirmation → dossier supprimé
  - Alerte : rédaction mail → envoi → fermeture modal
  - Annulation : aucune action effectuée

**Validation :**
- ✅ Modals responsive (mobile/desktop)
- ✅ Pas de fuite mémoire TinyMCE (destroy après fermeture)
- ✅ Messages de confirmation clairs

---

### 🟡 Bloc 10 : Nettoyage et optimisations finales

**Complexité :** 🟡 Moyenne (refactoring, tests)

**Tâches :**
- [ ] **10.1** 🆕 Refactoring modulaire de form.js (si > 1200 lignes)
  - **DÉCISION 27/01/2026 :** Architecture modulaire reportée au Bloc 10
  - **Contexte :** form.js actuellement à 770 lignes (35% logique avis)
  - **Action si nécessaire :**
    - Créer `public/js/dossier/modules/avis.js` (~300 lignes)
    - Créer `public/js/dossier/modules/documents.js` (~200 lignes)
    - Créer `public/js/dossier/modules/validation.js` (~150 lignes)
    - Créer `public/js/dossier/modules/urbanisme.js` (~100 lignes)
    - Refactor form.js en orchestrateur (~200 lignes)
  - **Communication :** Événements custom pour inter-modules
  - **Justification :** Principe SRP + maintenabilité à long terme
  - **Référence :** `~/.copilot/session-state/.../files/architecture-modulaire-avis.md`

- [ ] **10.2** Supprimer fichiers legacy obsolètes
  - Supprimer `dossierGeneral.js` (remplacé par `form.js`)
  - Supprimer versions `-bs3.js` si Bootstrap 3 confirmé partout
  
- [ ] **10.3** Centraliser utilitaires communs
  - Créer `public/js/common/utils.js` pour :
    - `getTodayDate()` (utilisé partout)
    - `formatDate()`, `parseDate()`
    - `debounce()`, `throttle()` si nécessaire
  
- [ ] **10.4** Documenter architecture finale
  - Créer `docs/tech/dossier/ARCHITECTURE_JS.md`
  - Diagramme de dépendances entre modules JS
  - Guide pour ajouter une nouvelle interaction
  
- [ ] **10.5** Tests de non-régression exhaustifs
  - Checklist complète : 68 natures × 4 types = 272 combinaisons
  - Focus sur 20-30 cas représentatifs (80/20)
  - Tester sur Firefox, Chrome, Safari (si possible)
  
- [ ] **10.6** Audit performance
  - Mesurer temps de chargement page edit dossier
  - Mesurer temps de réponse changement type/nature
  - Optimiser si > 500ms (lazy load, debounce, etc.)

**Validation :**
- ✅ PHPStan niveau 10 (si PHP modifié)
- ✅ Pas d'erreur JS dans console (tous navigateurs)
- ✅ Lighthouse score > 90 (performance)
- ✅ Documentation à jour
- ✅ Architecture modulaire claire (si refactoring appliqué)

---

## 🎯 Ordre d'exécution recommandé

**✅ Complétés (27 janvier 2026) :**
1. ✅ **Phase 0** - Inventaire détaillé (~2h)
2. ✅ **Bloc 1** - Visibilité TYPE/NATURE (~6h) - Tests validés ✅
3. ✅ **Bloc 2** - Autocomplete AJAX (~4h) - Tests validés ✅
4. ✅ **Bloc 3** - Calendrier (~6h) - Tests validés ✅

**Priorité 1 (Fonctionnalités clés) :**
5. **Bloc 6** - Avis & Dérogations (2-3h) - Partiellement fait, à compléter
6. **Bloc 8** - Plat'AU (3-4h) - Si utilisé dans votre contexte

**Priorité 2 (Qualité de vie) :**
7. **Bloc 7** - Boutons "Aujourd'hui" (1h) - Déjà implémenté, à vérifier
8. **Bloc 4** - Documents urbanisme (1-2h) - Déjà partiellement fait
9. **Bloc 5** - Validation formulaire (2-3h)

**Priorité 3 (Nice to have) :**
10. **Bloc 9** - Modals/Alertes (2h) - ⚠️ Nécessite migration backend
11. **Bloc 10** - Nettoyage final (3-4h)

**Temps consacré :** ~16 heures (Blocs 0-3)
**Temps estimé restant :** ~15-20 heures

---

## 📝 Notes importantes

### Décisions architecturales (VALIDÉES)

1. **✅ AJAX vs POST classique pour sauvegarde :**
   - Legacy : AJAX avec validation côté client
   - Symfony best practice : POST classique + handleRequest
   - **DÉCISION VALIDÉE :** POST classique (plus simple, plus robuste)

2. **✅ TomSelect vs autre lib autocomplete :**
   - Déjà utilisé pour service instructeur
   - **DÉCISION VALIDÉE :** Uniformiser avec TomSelect partout

3. **✅ Garder jQuery ou migrer Vanilla JS :**
   - Legacy : jQuery 1.x obligatoire (FullCalendar v1)
   - Migré : Mix jQuery (FullCalendar) + Vanilla (gestion-incomplet.js)
   - **DÉCISION VALIDÉE :** Vanilla JS prioritaire, jQuery uniquement si nécessaire

4. **✅ Structure des modules JS :**
   - Pattern IIFE pour éviter pollution scope global
   - Strict mode systématique
   - Exports explicites si besoin communication inter-modules

### Risques identifiés

- 🔴 **Routes backend manquantes** : /changement/alerteform et /changement/sendmailalerte NON migrées (CONFIRMÉ) - Bloque Bloc 9.3 - Action : Créer controllers Symfony dédiés
- ⚠️ **Séparation affichage/édition** : Fonctionnalités avec reload page (retry-export) ne sont PAS sur page édition
- ⚠️ **API Plat'AU** : Dépendance externe, tester avec fixtures si API indispo
- ⚠️ **Performance** : 68 natures × champs dynamiques = beaucoup de DOM manipulation
- ⚠️ **Tests manuels chronophages** : 272 combinaisons théoriques, prioriser

### Critères de succès

✅ **Fonctionnel :**
- Toutes les interactions legacy reproduites à l'identique
- Aucune régression sur fonctionnalités existantes
- Formulaire utilisable sur mobile (responsive)

✅ **Qualité code :**
- PHPStan niveau 10 (0 erreur)
- ESLint (ou équivalent) 0 warning
- Code couvert par tests (au moins logique métier)

✅ **Performance :**
- Chargement page < 2s (3G)
- Changement type/nature < 500ms
- Pas de memory leak (test 30min d'utilisation continue)

✅ **Documentation :**
- Architecture JS documentée
- Guide ajout nouvelle interaction
- Inventaire legacy → migré complet

---

## 📚 Références

- **Legacy :** `prevarisc/application/views/scripts/dossier/index.phtml`
- **Migré :** `prevarisc-migration/templates/dossier/edit.html.twig`
- **Config champs :** `prevarisc-migration/src/Service/DossierFieldsService.php`
- **Mapping :** `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md`
- **Instructions :** `.github/edition-dossier.md`

---

**Dernière mise à jour :** 2026-01-26  
**Statut :** 🟢 Phase 0 terminée - Prêt pour migration (Blocs 1-9)
