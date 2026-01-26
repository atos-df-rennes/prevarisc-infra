# Phase 0.2 - Analyse Comparative Legacy vs Migré

**Date :** 2026-01-23  
**Objectif :** Identifier les écarts entre le legacy et les fichiers JS migrés

---

## 📊 Vue d'ensemble

| Fichier | Lignes | Statut | Taux migration |
|---------|--------|--------|----------------|
| `form.js` | 671 | 🟡 85% | Bon, manque AJAX autocomplete |
| `calendar-init.js` | 477 | 🟡 85% | Bon, à vérifier conformité |
| `gestion-incomplet.js` | 669 | ✅ 95% | Quasi complet, sync avis |
| `commission.js` | 68 | 🟡 70% | Manque masquage calendriers |
| `platau.js` | 155 | 🟡 75% | Manque boutons retry-export |

**Total lignes migrées :** 2040 lignes JS modernes  
**Legacy :** 2567 lignes inline mélangées HTML

---

## 🔍 Analyse détaillée par fichier

### 1. form.js (671 lignes) - ⭐ Fichier principal

**Responsabilité :** Gestion centrale du formulaire dossier (type, nature, champs conditionnels)

#### ✅ Fonctionnalités migrées (10/13)

1. **Changement TYPE dossier** (Ligne 88-100)
   - Event : `#dossier_type` change
   - Charge natures disponibles via AJAX
   - Masque/affiche sections selon type
   - ✅ Implémenté dans `onTypeChange()`

2. **Changement NATURE dossier** (Ligne 107-114)
   - Event : `#dossier_nature` change
   - Affiche champs conditionnels selon nature
   - ✅ Implémenté dans `onNatureChange()`

3. **Visibilité conditionnelle champs** (Ligne 142-200)
   - Masque/affiche champs selon `fieldsConfig`
   - ✅ Implémenté dans `updateFieldsVisibility()`
   - Note : Configuration chargée depuis `DossierFieldsService.php`

4. **Boutons "Aujourd'hui"** (Ligne 515-535)
   - Class `.btn-today` avec `data-target`
   - Remplit date du jour (YYYY-MM-DD)
   - ✅ Implémenté dans `initTodayButtons()`
   - Note : Meilleure approche que legacy (délégation propre)

5. **TomSelect Service Instructeur** (Ligne 541-570)
   - Champ `#dossier_serviceInstructeur`
   - Autocomplete local (sans AJAX pour l'instant)
   - ✅ Base implémentée, AJAX à ajouter

6. **Auto-préfixe Documents Urbanisme** (Ligne 597-622)
   - Préfixes : PC, AT, PA, DP, CU + année
   - ✅ Implémenté dans `initDocumentsUrbanismeHandlers()`

7. **Affichage facteur/échéancier si avis défavorable** (Ligne 413-430)
   - Si `avisCommission` = 2 (Défavorable) → affiche champs
   - ✅ Implémenté dans `updateFacteurEcheancierDisplay()`
   - TODO : Tester exhaustivement

8. **Labels avis dynamiques** (Ligne 342-367)
   - Change labels avis selon type/nature
   - Ex : "Avis de la commission" vs "Favorable à réception travaux"
   - ✅ Implémenté dans `updateAvisLabels()`

9. **Affichage Analyse de Risque** (Ligne 494-510)
   - Visible uniquement pour types 1, 2, 3
   - ✅ Implémenté dans `updateAnalyseDeRisqueDisplay()`

10. **Collection Symfony Documents Urbanisme** (Ligne 625-637)
    - Event `collection:item-added` custom
    - ✅ Gestion propre des prototypes Symfony

#### 🟡 Partiellement implémenté (4/13)

11. **AJAX Chargement natures** (Ligne 121-140)
    - Route : `/api/dossier/natures-by-type/{typeId}`
    - ✅ IMPLÉMENTÉ : Route backend Symfony + Repository
    - ✅ Frontend appelle via fetch() correctement
    - ✅ Tests : DossierNatureListeRepositoryTest (4 tests passent)
    - Status : **100% migré** (Bloc 1 terminé)

12. **Masquage avis si incomplet** (Ligne 369-390)
    - Classe `.hideavis` sur section avis
    - 🟡 Partiellement implémenté dans `applyHideAvisLogicOnLoad()`
    - TODO : Synchroniser avec `gestion-incomplet.js` (délégation events)

13. **Autocomplete Préventionnistes** (Ligne 541-570)
    - Champ préventionnistes
    - 🟡 TomSelect initialisé, manque AJAX load
    - Route : `/api/1.0/search/users` (fonction 13)
    - TODO : Ajouter callback `load()` pour requête AJAX (Bloc 2.2)

14. **Autocomplete Ville Service Instructeur** (Ligne 541-570)
    - Champ `#dossier_serviceInstructeur` (ville)
    - 🟡 TomSelect initialisé, manque AJAX load
    - Route backend à identifier
    - TODO : Ajouter callback `load()` pour requête AJAX (Bloc 2.3)

**Écarts fonctionnels identifiés :**
- Aucune validation côté client (volontaire, Symfony prioritaire)
- AJAX autocomplete à compléter (bases TomSelect déjà en place)

---

### 2. calendar-init.js (477 lignes) - 🗓️ Calendrier

**Responsabilité :** Gestion FullCalendar pour commissions et visites

#### ✅ Fonctionnalités migrées (8/10)

1. **Initialisation FullCalendar** (Ligne 1-100)
   - Options : selectable, editable, locale fr
   - ✅ Implémenté

2. **Chargement événements AJAX** (Ligne 120-150)
   - Route : `/calendrier-des-commissions/recupevenement`
   - Format événements parsé
   - ✅ Implémenté dans callback `events`

3. **Sélection date → remplis champ** (Ligne 200-240)
   - Click date calendrier → remplit `dateCommission` ou `dateVisite`
   - Masque calendrier après sélection
   - ✅ Implémenté dans `select` callback

4. **Boutons Afficher/Masquer calendrier** (Intégré dans DOM)
   - Class `.show-calendar` et `.hide-calendar`
   - ✅ Listeners ajoutés (ligne 450+)

5. **Drag & Drop événements** (Ligne 280-310)
   - Si `editable: true` → AJAX update
   - Route : `/calendrier-des-commissions/adddates`
   - ✅ Implémenté dans `eventDrop`

6. **Resize événements** (Ligne 320-350)
   - Même logique que drag & drop
   - ✅ Implémenté dans `eventResize`

7. **Click événement** (Ligne 360-380)
   - Ouvre modal détails (à confirmer)
   - ✅ Callback `eventClick` présent

8. **Dates liées (alertes)** (Ligne 130-160)
   - Template `#dates-liees__template`
   - Affiche alertes si dates déjà prises
   - ✅ Implémenté

#### 🟡 À vérifier (1/10)

9. **Conformité complète avec legacy plugin**
   - Legacy : `$.fn.afficheCalendar()` (~450 lignes)
   - Migré : Approche moderne FullCalendar v3
   - 🟡 Comparer fonctionnalité par fonctionnalité (Bloc 3.1-3.2)

#### ❌ Non migré (1/10)

10. **Modal détails événement** (Legacy ligne ~700)
    - AJAX `/calendrier-des-commissions/dialogcomm`
    - Ouvre dialog jQuery UI
    - ❌ Non implémenté (Bloc 3.4)

**Écarts fonctionnels identifiés :**
- Gestion weekends désactivés à vérifier
- Modal détails événement manquant

---

### 3. gestion-incomplet.js (669 lignes) - ✅ COMPLET

**Responsabilité :** Workflow documents manquants (incomplet ↔ complet)

#### ✅ Fonctionnalités migrées (9/9) - 100%

1. **Clic radio "Incomplet"** (Ligne 208-248)
   - Affiche zone documents manquants
   - Ajoute document depuis prototype Symfony
   - Masque champs hors délai
   - ✅ `handleIncompletChangeModerne()`

2. **Ajout document manquant** (Ligne 426-495)
   - Utilise `data-prototype` Symfony Collection
   - Pré-remplit date du jour
   - Charge liste documents depuis `/api/document-manquant/liste`
   - ✅ `addDocumentFromPrototype()`

3. **Validation réception** (Ligne 252-279)
   - Vérification date saisie
   - Marque document readonly
   - Retour auto à "Complet"
   - ✅ `handleValidateReception()`

4. **Annulation incomplet** (Ligne 285-297)
   - Supprime bloc document
   - Retour à "Complet"
   - ✅ `handleCancelIncomplet()`

5. **Bouton "Modifier"** (Ligne 501-562)
   - Rend champs éditables (sauf date incomplet)
   - Vide date réception
   - Retour à "Incomplet"
   - ✅ `handleModifierReception()`

6. **Visibilité boutons conditionnelle** (Ligne 570-636)
   - "Annuler" : seulement nouveau document
   - "Modifier" : seulement dernier validé
   - "Valider" : documents non validés
   - ✅ `updateButtonsVisibility()`

7. **Boutons "Aujourd'hui"** (Ligne 399-421)
   - Date incomplet (readonly)
   - Date réception (éditable)
   - ✅ `handleTodayIncomplet()` et `handleTodayReception()`

8. **Historique accordéon** (Ligne 645-664)
   - Icône folder-open/close
   - ✅ `initHistoriqueAccordion()`

9. **Délégation d'événements** (Ligne 141-157)
   - Event listeners sur `document`
   - Gère éléments ajoutés dynamiquement
   - ✅ Pattern moderne implémenté

#### 🟡 À synchroniser (1 item)

10. **Classe `.hideavis`** (Legacy ligne 359-398)
    - Masque section avis si incomplet
    - 🟡 Logique présente mais à lier avec `form.js`
    - TODO : Event custom ou fonction partagée

**Écarts fonctionnels identifiés :** Aucun ! Fichier quasi parfait.

---

### 4. commission.js (68 lignes) - 🟡 Basique

**Responsabilité :** Gestion changement commission

#### ✅ Fonctionnalités migrées (2/3)

1. **Changement commission → vider dates** (Ligne 10-30)
   - Event : `#dossier_commission` change
   - Vide `dateVisite` et `dateCommission`
   - ✅ Implémenté

2. **Alertes dates liées** (Ligne 40-60)
   - Affiche alertes si dates déjà prises
   - ✅ Implémenté

#### ❌ Non migré (1/3)

3. **Masquage calendriers si changement** (Legacy ligne 170-192)
   - Bouton `.changeCommission` click
   - Masque calendriers visibles
   - ❌ Non implémenté
   - Bloc 3.3 du plan

**Écarts fonctionnels identifiés :**
- Interaction avec calendrier manquante

---

### 5. platau.js (155 lignes) - 🟡 À compléter

**Responsabilité :** Affichage conditionnel bloc pièces jointes Plat'AU + actions retry export

#### ✅ Fonctionnalités migrées (5/7)

1. **Affichage conditionnel PJ** (Ligne 38-113)
   - Condition : incomplet OU avis défini
   - ✅ `initGestionPiecesJointesPlatau()`

2. **Changement incomplet → affiche PJ** (Ligne 53-60)
   - Event : `#dossier_incomplet_1` change
   - ✅ Listener ajouté

3. **Changement avis → affiche PJ** (Ligne 71-85)
   - Event : `#dossier_avisCommission` change
   - ✅ Listener ajouté

4. **Badge alerte + scroll** (Ligne 88-95)
   - Click `#select-platau` → scroll vers `#field-piecesJointesPlatau`
   - ✅ Implémenté

5. **Affichage initial selon statut** (Ligne 98-112)
   - Vérifie classes `.text-warning` sur statuts
   - ✅ Logique au chargement

#### ❌ Non migré (2/7) - ÉCARTS IDENTIFIÉS

6. **Bouton retry-export PEC** (Legacy ligne 4-14, Code ligne 2-13)
   - ❌ Bouton `#retry-export-pec` NON présent dans template édition
   - Route : `POST /platau/retry-export-pec`
   - Action : Relance export PEC vers Plat'AU
   - TODO : Ajouter bouton dans template Twig + gérer réponse (toast notification)
   - Note : Code JS existe déjà (ligne 2-13), attendre ajout HTML

7. **Bouton retry-export Avis** (Legacy ligne 16-28, Code ligne 15-27)
   - ❌ Bouton `#retry-export-avis` NON présent dans template édition
   - Route : `POST /platau/retry-export-avis`
   - Action : Relance export Avis vers Plat'AU
   - TODO : Ajouter bouton dans template Twig + gérer réponse (toast notification)
   - Note : Code JS existe déjà (ligne 15-27), attendre ajout HTML

**Écarts fonctionnels identifiés :**
- Boutons retry-export manquants dans HTML (fonctionnalité importante pour utilisateurs)
- JS prêt, attendre ajout dans template `edit.html.twig`

---

### 6. Délégation d'événements récente (fix)

**Fichier :** `platau.js` (Ligne 62-67)  
**Problème identifié :** Bouton `.cancel-incomplet` ajouté dynamiquement  
**Solution :** Délégation sur `document` (fixé récemment)

```javascript
// ❌ AVANT (ne fonctionnait pas)
const btnCancelIncomplet = document.querySelector('.cancel-incomplet')
if (btnCancelIncomplet) {
    btnCancelIncomplet.addEventListener('click', ...)
}

// ✅ APRÈS (fonctionne)
document.addEventListener('click', function(e) {
    if (e.target.closest('.cancel-incomplet')) {
        masquePiecesJointesPlatau(...)
    }
})
```

---

## 📋 Synthèse des écarts

### ✅ Bien migré (pas d'action)
- Documents manquants workflow (gestion-incomplet.js)
- Affichage conditionnel PJ Plat'AU (platau.js - logique présente)
- Boutons "Aujourd'hui" dates (form.js)
- Auto-préfixe documents urbanisme (form.js)
- Labels avis dynamiques (form.js)
- Visibilité conditionnelle champs (form.js)
- Bases TomSelect autocomplete (form.js - manque AJAX)

### 🟡 À compléter (prioritaires)

| Fonctionnalité | Fichier cible | Bloc plan | Complexité | Note |
|----------------|---------------|-----------|------------|------|
| AJAX autocomplete préventionnistes | `form.js` | 2.2 | 🟢 Facile | TomSelect déjà initialisé |
| AJAX autocomplete ville | `form.js` | 2.3 | 🟢 Facile | TomSelect déjà initialisé |
| Boutons retry-export PEC/Avis | `platau.js` + Twig | 8.5 | 🟡 Moyen | JS prêt, ajouter HTML + toast |
| Masquage calendriers si changement commission | `commission.js` | 3.3 | 🟢 Facile | - |
| Modal détails événement calendrier | `calendar-init.js` | 3.4 | 🟡 Moyen | - |
| Synchronisation hideavis incomplet ↔ avis | `form.js` | 6.3 | 🟢 Facile | - |

### ❌ Non migré (bloqués)

| Fonctionnalité | Bloquant | Bloc plan |
|----------------|----------|-----------|
| Modal alerte établissement | 🔴 Routes `/changement/*` manquantes | 9.3 |
| Modal suppression dossier | Aucun (à faire) | 9.2 |

### ⚠️ Non applicable (volontaire)

- Sauvegarde AJAX (POST classique Symfony)
- Validation client complexe (Symfony Constraints)
- Boutons retry-export en édition (séparation pages)

---

## 🎯 Actions recommandées

### Priorité 1 - Quick wins (faciles, impact utilisateur)
1. **Bloc 2.2-2.3** : Compléter AJAX autocomplete (2h) - TomSelect déjà en place
2. **Bloc 1.3** : Synchronisation hideavis (1h)
3. **Bloc 3.3** : Masquage calendriers commission (30min)

### Priorité 2 - Fonctionnalités importantes
4. **Bloc 8.5** : Boutons retry-export Plat'AU (2h)
   - Ajouter boutons HTML dans template
   - Gérer notifications toast (succès/erreur)
   - JS déjà prêt
5. **Bloc 3.4** : Modal détails événement calendrier (2h)

### Priorité 3 - Nice to have
6. **Bloc 9** : Modals (attendre backend pour alertes)

### Fichiers à supprimer
- `dossierGeneral.js` (remplacé par form.js)
- `dossierGeneral-bs3.js`
- `drop-list-button-bs3.js`
- `avisDerogation-bs3.js`
- Tous les `-bs3.js` (doublons)

---

**Dernière mise à jour :** 2026-01-23  
**Statut :** ✅ Phase 0.2 terminée - Écarts identifiés et documentés

---

## 📝 Corrections apportées (2026-01-23)

### Erreurs corrigées :

1. **Autocomplete (form.js)** :
   - ❌ AVANT : "Non migré - TomSelect avec AJAX non implémenté"
   - ✅ APRÈS : "Partiellement implémenté - TomSelect initialisé, manque callback AJAX load()"
   - Impact : Réduit effort de 4h à 2h (bases déjà en place)

2. **Boutons retry-export (platau.js)** :
   - ❌ AVANT : "Non applicable - séparation affichage/édition"
   - ✅ APRÈS : "Non migré - boutons manquants dans HTML, JS prêt"
   - Impact : Identifié comme écart fonctionnel à combler (Bloc 8.5)
   - Action : Ajouter boutons HTML + notifications toast

3. **Taux migration platau.js** :
   - ❌ AVANT : 90% (5/5 fonctionnalités)
   - ✅ APRÈS : 75% (5/7 fonctionnalités)
   - Raison : Retry-export comptabilisé comme manquant

**Taux global mis à jour :** ~85% (inchangé, réévaluation précise des écarts)
