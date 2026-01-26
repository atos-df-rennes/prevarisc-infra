# Guide de tests - Interactions JavaScript du formulaire dossier

**Date :** 2026-01-26  
**Objectif :** Documenter comment tester exhaustivement toutes les interactions JavaScript migrées

---

## 📋 Vue d'ensemble

Ce guide décrit :
1. Les fixtures nécessaires (types, natures, dossiers de test)
2. Les scénarios de test par fonctionnalité
3. Les checklist de validation manuelle
4. Les cas limites à vérifier

**Environnement de test :**
- URL : http://localhost:7080
- Base de données : `PRV_prevarisc_v2` (développement)
- Navigateurs cibles : Chrome 90+, Firefox 88+, Safari 14+ (iOS/macOS)

---

## 🎯 Données de référence

### Types de dossier (7 types)

| ID | Libellé | Natures associées |
|----|---------|-------------------|
| 1 | Étude | 26 natures (PC, AT, Dérogation, etc.) |
| 2 | Visite de commission | 7 natures (Périodique, Réception, etc.) |
| 3 | Groupe de visite | 6 natures (Périodique, Réception, etc.) |
| 4 | Réunion | 3 natures (Locaux SDIS, Extérieur SDIS, Téléphonique) |
| 5 | Courrier / Courriel | 12 natures (Lettre, Mise en demeure, etc.) |
| 6 | Intervention | 3 natures (Incendie, SAP, Inter. div.) |
| 7 | Arrêté | 5 natures (Ouverture, Fermeture, etc.) |

**Total : 62 natures**

### Natures représentatives pour tests (20 sélectionnées)

**Type 1 - Étude (10 natures testées sur 26) :**
1. Nature 1 - Permis de construire (PC)
2. Nature 2 - Autorisation de travaux (AT)
3. Nature 3 - Dérogation
4. Nature 4 - Cahier des charges fonctionnel du SSI
5. Nature 7 - Levée de prescriptions
6. Nature 18 - Utilisation exceptionnelle de locaux
7. Nature 19 - Levée de réserves suite à un avis défavorable
8. Nature 30 - Déclaration préalable
9. Nature 61 - Autorisation d'une ICPE
10. Nature 66 - Déclassement / Reclassement

**Type 2 - Visite de commission (5 natures testées sur 7) :**
11. Nature 20 - Réception de travaux
12. Nature 21 - Périodique
13. Nature 22 - Chantier
14. Nature 23 - Contrôle
15. Nature 47 - Avant ouverture

**Type 3 - Groupe de visite (2 natures testées sur 6) :**
16. Nature 25 - Réception de travaux
17. Nature 26 - Périodique

**Type 5 - Courrier / Courriel (2 natures testées sur 12) :**
18. Nature 52 - Lettre
19. Nature 55 - Mise en demeure

**Type 7 - Arrêté (1 nature testée sur 5) :**
20. Nature 40 - Ouverture

**Couverture :** 20/62 natures = 32% (représentative selon règle 80/20)

---

## 🧪 Scénarios de test par fonctionnalité

### 🟢 Bloc 1 : Visibilité conditionnelle des champs (Type & Nature)

**Fichier :** `public/js/dossier/form.js`

#### Test 1.1 - Changement de TYPE dossier

**Préparation :**
1. Créer un nouveau dossier (bouton "Nouveau dossier")
2. Page d'édition affichée

**Scénario :**

| Action | Type sélectionné | Champs attendus visibles | Champs attendus masqués |
|--------|------------------|-------------------------|-------------------------|
| 1 | Type 1 (Étude) | Nature, Objet, Date insert, Service instructeur, Documents urbanisme, Date commission, Date visite, Avis, Prescriptions | Date dernier |
| 2 | Type 2 (Visite commission) | Nature, Objet, Date insert, Date commission, Date visite, Avis, Prescriptions | Service instructeur, Documents urbanisme |
| 3 | Type 3 (Groupe visite) | Nature, Objet, Date insert, Date visite, Avis, Prescriptions | Date commission, Service instructeur |
| 4 | Type 4 (Réunion) | Nature, Objet, Date insert | Date commission, Date visite, Avis, Service instructeur |
| 5 | Type 5 (Courrier) | Nature, Objet, Date insert | Date commission, Date visite, Avis, Prescriptions |

**Validation :**
- ✅ Champs masqués ont la classe `.d-none` (Bootstrap 3)
- ✅ Champs visibles n'ont PAS la classe `.d-none`
- ✅ Transitions fluides (pas de clignotement)
- ✅ Aucune erreur console JS

**Console à vérifier :**
```javascript
console.log('Type changed to:', typeId);
console.log('Visible fields:', visibleFields);
```

---

#### Test 1.2 - Changement de NATURE dossier

**Préparation :**
1. Sélectionner Type 1 (Étude)
2. Champs conditionnels affichés selon nature

**Scénario :**

| Action | Nature sélectionnée | Champs supplémentaires attendus |
|--------|---------------------|--------------------------------|
| 1 | Nature 1 (PC) | Objet, Date dépôt, Date commission, Date visite |
| 2 | Nature 3 (Dérogation) | Objet, Texte applicable, Date commission |
| 3 | Nature 7 (Levée prescriptions) | Objet, Texte applicable, Avis dossier lié |
| 4 | Nature 19 (Levée réserves) | Objet, Avis dossier lié, Date commission |
| 5 | Nature 30 (Déclaration préalable) | Objet, Date dépôt, Documents urbanisme |

**Validation :**
- ✅ Configuration champs correcte (vérifier `DossierFieldsService.php`)
- ✅ Ordre d'affichage logique (champs principaux → champs conditionnels)
- ✅ Labels corrects pour chaque nature
- ✅ Aucun champ ne "saute" (animation smooth)

**Cas limites :**
- Changer type PUIS nature → vérifier cohérence
- Changer nature plusieurs fois de suite (5+) → pas de memory leak

---

#### Test 1.3 - Interdépendance INCOMPLET ↔ AVIS/COMMISSION

**Préparation :**
1. Créer dossier Type 1, Nature 1 (PC)
2. Sélectionner une commission
3. Définir un avis

**Scénario :**

| Action | État incomplet | Comportement attendu |
|--------|----------------|---------------------|
| 1 | Cocher "Incomplet" | Section avis masquée (classe `hideavis`), calendrier masqué |
| 2 | Décocher "Incomplet" | Section avis réaffichée, calendrier réaffiché |
| 3 | Cocher + Sauvegarder + Recharger | Avis reste masqué (persistance) |
| 4 | Décocher "Incomplet" avec avis déjà défini | Avis précédemment saisi réaffiché (pas de perte données) |

**Validation :**
- ✅ Synchronisation temps réel (pas de délai > 200ms)
- ✅ Pas de perte de données saisies dans champs masqués
- ✅ Badge documents manquants cohérent avec état incomplet

**Fichiers impliqués :**
- `form.js` - Gestion affichage avis
- `gestion-incomplet.js` - Gestion état incomplet
- `calendar-init.js` - Masquage calendrier

---

### 🟡 Bloc 2 : Auto-complétion et recherches asynchrones

**Fichier :** `public/js/dossier/form.js` (TomSelect)

#### Test 2.1 - Autocomplete Service Instructeur

**⚠️ Statut actuel :** Autocomplete local (sans AJAX) - Déjà migré

**Scénario :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Focus sur champ "Service instructeur" | Dropdown TomSelect avec liste complète |
| 2. Taper "Mar" | Filtrage local → affiche "Marseille", "Marignane", etc. |
| 3. Sélectionner "Marseille" | Champ rempli, dropdown fermé |
| 4. Vider champ + re-sélectionner | Pas de duplication, pas d'erreur |

**Validation :**
- ✅ TomSelect initialisé correctement
- ✅ Filtrage case-insensitive
- ✅ Support accents (recherche "Marseille" trouve "Marseille")
- ✅ Mobile-friendly (touch events)

**Note migration :** Legacy utilisait AJAX `/api/1.0/search/users` avec préventionnistes, mais version migrée utilise dropdown local pour services instructeurs (modification UX validée).

---

#### Test 2.2 - Autocomplete Préventionnistes (future migration)

**⚠️ Non encore migré en AJAX** - Actuellement local comme service instructeur

**Scénario prévu (post-migration) :**

| Action | API appelée | Résultat attendu |
|--------|-------------|------------------|
| 1. Taper "Dup" | POST `/api/1.0/search/users` (fonctions=13, limit=100) | Suggestions : "DUPONT Jean", "DURAND Pierre" |
| 2. Sélectionner "DUPONT Jean" | - | Champ rempli avec `data-id="42"`, trigger change |
| 3. Effacer + re-taper "Dup" | Cache local OU nouvel AJAX | Mêmes résultats (cohérence) |

**Validation post-migration :**
- ✅ Délai AJAX < 300ms (debounce 250ms recommandé)
- ✅ Gestion erreur réseau (timeout, 500) → message clair
- ✅ Limite 100 résultats respectée
- ✅ Format affichage : "NOM Prénom" (uppercase NOM)

**Bloquant :** Vérifier existence route `/api/1.0/search/users` en Symfony

---

### 🔴 Bloc 3 : Calendrier commissions & visites

**Fichier :** `public/js/dossier/calendar-init.js`

#### Test 3.1 - Affichage/masquage calendrier COMMISSION

**Préparation :**
1. Type 1 ou 2 (avec commission)
2. Sélectionner une commission dans dropdown

**Scénario :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Clic bouton "Afficher calendrier commission" | Calendrier FullCalendar affiché sous champ dateCommission |
| 2. Vérifier événements | Dates commission chargées depuis `/calendrier-des-commissions/recupevenement` |
| 3. Clic sur date disponible | Date sélectionnée → remplit champ `dateCommission` (format YYYY-MM-DD) |
| 4. Calendrier se masque automatiquement | Calendrier masqué après sélection |
| 5. Clic bouton "Masquer calendrier" | Calendrier disparaît |

**Validation :**
- ✅ AJAX route backend valide et réponse 200
- ✅ Format dates cohérent (ISO 8601)
- ✅ Drag & drop désactivé (lecture seule)
- ✅ Responsive mobile (touch select)

**Cas limites :**
- Aucune date disponible → calendrier vide avec message
- Commission avec 50+ dates → performance OK (< 2s chargement)
- Changer commission AVEC date déjà sélectionnée → date vidée + nouveau calendrier

---

#### Test 3.2 - Affichage/masquage calendrier VISITE

**Préparation :**
1. Type 2 (Visite commission)
2. Sélectionner une commission

**Scénario :** Identique au Test 3.1, mais avec :
- Bouton "Afficher calendrier visite"
- Champ cible : `dateVisite`
- Route AJAX : Même que commission (à confirmer selon backend)

**Validation :**
- ✅ Même comportement que calendrier commission
- ✅ Deux calendriers indépendants (pas d'interférence)

---

#### Test 3.3 - Synchronisation changement COMMISSION

**Scénario :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Sélectionner Commission A + Date | `dateCommission` = "2026-03-15" |
| 2. Changer pour Commission B | Champs `dateCommission` ET `dateVisite` vidés automatiquement |
| 3. Afficher calendrier Commission B | Nouvelles dates affichées |
| 4. Sélectionner nouvelle date | `dateCommission` = "2026-04-20" |

**Validation :**
- ✅ Vidage automatique dates précédentes (évite incohérence)
- ✅ Rechargement calendrier avec bonnes données
- ✅ Synchronisation entre `commission.js` et `calendar-init.js`

**Fichiers impliqués :**
- `commission.js` - Event `change` sur select commission
- `calendar-init.js` - Fonction `reloadCalendar()`

---

### 🟢 Bloc 4 : Documents d'urbanisme (ajout/suppression)

**Fichier :** `public/js/dossier/form.js` (section documents)

#### Test 4.1 - Auto-préfixe documents

**Préparation :**
1. Type 1 (Étude), Nature 1 (PC)
2. Section "Documents d'urbanisme" visible

**Scénario :**

| Action | Champ type doc | Valeur saisie | Préfixe attendu | Résultat final |
|--------|----------------|---------------|-----------------|----------------|
| 1 | PC | `2023001` | `PC` | `PC2023001` |
| 2 | AT | `2024042` | `AT` | `AT2024042` |
| 3 | PA | `25-05` | `PA` | `PA25-05` |
| 4 | DP | `789` | `DP` | `DP789` |
| 5 | CU | `ABC123` | `CU` | `CUABC123` |

**Validation :**
- ✅ Préfixe ajouté automatiquement au blur (perte focus)
- ✅ Si préfixe déjà présent → pas de duplication (ex: `PCPC2023001` ❌)
- ✅ Respect majuscules/minuscules (PC, pas pc)

**Code vérifié :**
```javascript
// Ligne ~597-622 form.js
if (!value.toUpperCase().startsWith(prefix)) {
    input.value = prefix + value;
}
```

---

#### Test 4.2 - Ajout dynamique document

**Scénario :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Clic bouton "Ajouter document" | Nouveau bloc document apparaît (clone prototype Symfony) |
| 2. Index incrémenté | `name="dossier[documents][0]"` → `name="dossier[documents][1]"` |
| 3. Remplir champs | Type PC, Référence 2025001, Date 2025-01-15 |
| 4. Ajouter 2ème document | Index = 2, pas de conflit avec 1er document |

**Validation :**
- ✅ Prototype Symfony FormType correctement cloné
- ✅ IDs uniques pour chaque champ (éviter conflits HTML)
- ✅ Bouton suppression affiché sur chaque document (sauf 1er si minimum = 1)

---

#### Test 4.3 - Suppression document

**Scénario :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Ajouter 3 documents | 3 blocs visibles |
| 2. Clic bouton "Supprimer" sur document 2 | Bloc 2 disparaît (DOM removed) |
| 3. Sauvegarder formulaire | Persistance : 2 documents en base (1 et 3) |
| 4. Recharger page édition | 2 documents affichés (pas le supprimé) |

**Validation :**
- ✅ Suppression immédiate (pas de confirmation si nouveau doc)
- ✅ Si document existant en base → marqué pour suppression (champ caché)
- ✅ Aucun doublon en base après save
- ✅ Index réorganisés après suppression (0, 1 au lieu de 0, 2)

**Table concernée :** `dossierdocurba`

---

### 🟡 Bloc 5 : Validation et sauvegarde formulaire

**Fichier :** `public/js/dossier/form.js` + contrôleur Symfony

#### Test 5.1 - Boutons "Aujourd'hui"

**Scénario :**

| Champ date | Bouton visible ? | Résultat clic |
|------------|------------------|---------------|
| Date insert | ✅ Oui | Remplit date du jour (2026-01-26) |
| Date dépôt | ✅ Oui (si Type 1) | 2026-01-26 |
| Date commission | ✅ Oui (si Type 1/2) | 2026-01-26 |
| Date visite | ✅ Oui (si Type 2/3) | 2026-01-26 |
| Date dernier | ✅ Oui (si visible) | 2026-01-26 |
| Date PEC | ✅ Oui | 2026-01-26 |
| Échéancier travaux | ✅ Oui (si avis défavorable) | 2026-01-26 |

**Validation :**
- ✅ Format YYYY-MM-DD (compatible HTML5 `input[type=date]`)
- ✅ Bouton masqué si champ date masqué (cohérence visuelle)
- ✅ Délégation événements (fonctionne même sur champs ajoutés dynamiquement)

**Code vérifié :**
```javascript
// Ligne ~515-535 form.js
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-today')) {
        const targetId = e.target.dataset.target;
        document.getElementById(targetId).value = new Date().toISOString().split('T')[0];
    }
});
```

---

#### Test 5.2 - Validation côté serveur (POST classique)

**Scénario :**

| Données soumises | Validation attendue | Message erreur |
|------------------|---------------------|----------------|
| Objet vide | ❌ Erreur | "L'objet est obligatoire" |
| Type non défini | ❌ Erreur | "Le type est obligatoire" |
| Nature non définie | ❌ Erreur | "La nature est obligatoire" |
| Date commission < Date insert | ⚠️ Warning | "Date commission antérieure à date d'insertion" |
| Date visite format invalide | ❌ Erreur | "Format date invalide (YYYY-MM-DD attendu)" |
| Tout valide | ✅ Succès | "Dossier enregistré avec succès" |

**Validation :**
- ✅ Validation Symfony Validator (annotations dans Entity)
- ✅ Erreurs affichées au-dessus du formulaire (Bootstrap alert)
- ✅ Champs en erreur mis en évidence (classe `is-invalid`)
- ✅ Pas de perte de données en cas d'erreur (formulaire pré-rempli)

**Fichiers impliqués :**
- `src/Entity/Dossier.php` - Contraintes de validation
- `src/Form/Type/DossierType.php` - FormType
- `src/Controller/DossierController.php` - Action `edit()`
- `templates/dossier/edit.html.twig` - Affichage erreurs

---

#### Test 5.3 - Sauvegarde et redirection

**Scénario :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Remplir formulaire valide | Bouton "Enregistrer" actif |
| 2. Clic "Enregistrer" | POST vers `/dossier/{id}/edit` |
| 3. Backend validation OK | Redirection vers `/dossier/{id}` (affichage) |
| 4. Flash message affiché | "Dossier #123 modifié avec succès" (Bootstrap alert-success) |
| 5. Données persistées | Vérifier en base : champs bien mis à jour |

**Validation :**
- ✅ Redirection empêche double-soumission (POST-Redirect-GET pattern)
- ✅ Séparation affichage/édition respectée (pas de reload page édition)
- ✅ Transactions DB rollback si erreur (intégrité données)

---

### 🟡 Bloc 6 : Avis & Dérogations

**Fichier :** `public/js/dossier/form.js` (section avis)

#### Test 6.1 - Affichage conditionnel champs avis

**Préparation :**
1. Type 1 (Étude), Nature 1 (PC)
2. Dossier complet (incomplet = false)

**Scénario :**

| Avis sélectionné | ID | Champs supplémentaires visibles |
|------------------|----|---------------------------------|
| Aucun avis | 0 | Aucun (section avis masquée) |
| Favorable | 1 | Aucun |
| Défavorable | 2 | ✅ Facteur dangérosité, ✅ Échéancier travaux |
| Avis avec réserves | 3 | Aucun (ou selon config métier) |
| Différé | 4 | Aucun |

**Validation :**
- ✅ Affichage instantané (< 100ms)
- ✅ Champs "Facteur" et "Échéancier" requis si avis = 2
- ✅ Validation formulaire bloque si champs requis vides

**Code vérifié :**
```javascript
// Ligne ~413-430 form.js
function updateFacteurEcheancierDisplay() {
    const avis = document.getElementById('dossier_avisCommission').value;
    const facteurField = document.getElementById('field-facteur');
    const echeancierField = document.getElementById('field-echeancier');
    
    if (avis === '2') { // Défavorable
        facteurField.classList.remove('d-none');
        echeancierField.classList.remove('d-none');
    } else {
        facteurField.classList.add('d-none');
        echeancierField.classList.add('d-none');
    }
}
```

---

#### Test 6.2 - Masquage avis si INCOMPLET

**Scénario :**

| État dossier | Section avis affichée ? |
|--------------|-------------------------|
| Complet | ✅ Oui |
| Incomplet (coché) | ❌ Non (classe `hideavis`) |
| Incomplet → Complet | ✅ Oui (réapparaît) |
| Incomplet + Avis déjà saisi | Avis conservé mais masqué (pas de perte données) |

**Validation :**
- ✅ Synchronisation avec `gestion-incomplet.js`
- ✅ Classe CSS `hideavis` ajoutée/retirée dynamiquement
- ✅ Données avis préservées en base même si masquées

**Fichiers impliqués :**
- `gestion-incomplet.js` - Gestion état incomplet
- `form.js` - Gestion affichage section avis

---

#### Test 6.3 - Labels avis dynamiques

**Scénario :**

| Type | Nature | Label avis attendu |
|------|--------|--------------------|
| 1 | 1 (PC) | "Avis de la commission" |
| 2 | 20 (Réception) | "Favorable à réception travaux" |
| 1 | 7 (Levée prescriptions) | "Levée effective des prescriptions" |
| 1 | 19 (Levée réserves) | "Levée effective des réserves" |

**Validation :**
- ✅ Labels mis à jour au changement type/nature
- ✅ Configuration labels dans `DossierFieldsService.php` ou JSON
- ✅ Labels accessibles (attribut `for` correct sur `<label>`)

**Code vérifié :**
```javascript
// Ligne ~342-367 form.js
function updateAvisLabels(typeId, natureId) {
    const labelConfig = window.DossierConfig.avisLabels;
    const label = labelConfig[typeId][natureId] || 'Avis de la commission';
    document.querySelector('label[for="dossier_avisCommission"]').textContent = label;
}
```

---

### 🔴 Bloc 8 : Intégrations Plat'AU

**Fichier :** `public/js/dossier/platau.js`

#### Test 8.1 - Affichage conditionnel bloc Plat'AU

**Préparation :**
1. Dossier avec `objet` contenant "PLATAU" ou "Plat'AU" (détection)
2. OU Établissement lié à Plat'AU

**Scénario :**

| Condition | Bloc Plat'AU affiché ? |
|-----------|------------------------|
| Dossier incomplet | ✅ Oui |
| Dossier complet + Avis défini (≠ 0) | ✅ Oui |
| Dossier complet + Aucun avis | ❌ Non |
| Dossier NON Plat'AU | ❌ Non (bloc caché) |

**Validation :**
- ✅ Fonction `isDossierPlatau()` retourne `true/false` correctement
- ✅ Affichage/masquage dynamique (classe `d-none`)
- ✅ Synchronisation avec incomplet ET avis

**Code vérifié :**
```javascript
// platau.js
function isDossierPlatau() {
    const objet = document.getElementById('dossier_objet').value.toLowerCase();
    return objet.includes('platau') || objet.includes('plat\'au');
}

function gestionAffichageInfosPlatau() {
    const incomplet = document.getElementById('dossier_incomplet').checked;
    const avis = document.getElementById('dossier_avisCommission').value;
    const isIncompletOuAvisDefined = incomplet || (avis !== '' && avis !== '0');
    
    document.getElementById('bloc-platau').classList.toggle('d-none', !isIncompletOuAvisDefined);
}
```

---

#### Test 8.2 - Badge alerte Plat'AU

**Scénario :**

| Pièces jointes Plat'AU | Badge affiché ? | Action clic |
|------------------------|-----------------|-------------|
| Vides ou non renseignées | ✅ Oui (badge danger) | Scroll vers champ `#field-piecesJointesPlatau` |
| Renseignées (texte présent) | ❌ Non (badge masqué) | - |
| Vider champ après saisie | ✅ Oui (réapparaît) | Scroll |

**Validation :**
- ✅ Badge positionné près du label "Plat'AU"
- ✅ Scroll smooth vers champ cible (behavior: 'smooth')
- ✅ Focus automatique sur champ après scroll
- ✅ Badge responsive (visible mobile)

**HTML attendu :**
```html
<span id="badge-alerte-platau" class="badge badge-danger" style="display: none;">
    <i class="icon-warning"></i> Pièces jointes manquantes
</span>
```

---

#### Test 8.3 - Boutons retry export (NON APPLICABLE)

**⚠️ Clarification :** Ces boutons (`#retry-export-pec`, `#retry-export-avis`) sont sur la page **affichage** (`show.html.twig`), PAS sur édition (`edit.html.twig`).

**Raison :** Séparation affichage/édition → reload page = perte données formulaire non sauvegardé.

**Action :** Aucun test nécessaire pour Phase 0.3 (hors scope édition).

---

### 🟢 Bloc 9 : Alertes et modals

**Fichier :** `public/js/dossier/modals.js` (à créer)

#### Test 9.1 - Modal confirmation suppression

**⚠️ Note :** Suppression disponible uniquement sur page **affichage**, pas édition.

**Scénario (si applicable) :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Clic bouton "Supprimer dossier" | Modal Bootstrap s'ouvre (#confirm-modal) |
| 2. Clic "Annuler" | Modal se ferme, aucune action |
| 3. Clic "Confirmer" | POST vers `/dossier/{id}/delete` |
| 4. Suppression réussie | Redirection vers liste dossiers + flash "Dossier supprimé" |
| 5. Erreur suppression (dossier lié) | Message erreur "Impossible de supprimer : dossier lié à..." |

**Validation :**
- ✅ Modal responsive (mobile)
- ✅ Bouton "Confirmer" en rouge (danger)
- ✅ ESC ferme modal sans supprimer
- ✅ Suppression cascade vérifiée (ou empêchée si contraintes)

---

#### Test 9.2 - Modal alerte établissement (BLOQUÉ)

**⚠️ Statut :** Routes backend `/changement/alerteform` et `/changement/sendmailalerte` NON MIGRÉES en Symfony.

**Action requise AVANT test :**
1. Créer `src/Controller/ChangementController.php`
2. Créer routes :
   - `GET /changement/alerteform?etablissement={id}` → formulaire modal
   - `POST /changement/sendmailalerte` → envoi email
3. Migrer logique métier depuis legacy `ChangementController.php`

**Scénario (post-migration backend) :**

| Action | Résultat attendu |
|--------|------------------|
| 1. Clic lien ".alerte-link" | Modal s'ouvre avec TinyMCE |
| 2. Chargement formulaire | AJAX GET `/changement/alerteform?etablissement=123` |
| 3. Rédiger message alerte | TinyMCE fonctionnel (bold, italic, etc.) |
| 4. Clic "Envoyer" | AJAX POST `/changement/sendmailalerte` avec contenu HTML |
| 5. Envoi réussi | Modal se ferme + flash "Alerte envoyée" |
| 6. Fermeture modal | TinyMCE destroy (éviter memory leak) |

**Validation post-migration :**
- ✅ TinyMCE configuration cohérente avec autres modals
- ✅ Validation email côté serveur (destinataires valides)
- ✅ Gestion erreurs SMTP (timeout, serveur indisponible)
- ✅ Log envoi email (table `changement` ou équivalent)

**Test bloqué jusqu'à :** Migration backend Changement (Priorité basse selon roadmap)

---

## 📝 Checklist de validation globale

### 🎯 Conformité fonctionnelle

- [ ] **Toutes les interactions legacy reproduites** (référence : `LEGACY_JS_INVENTORY.md`)
- [ ] **Aucune régression détectée** (comparaison side-by-side legacy vs migré)
- [ ] **Formulaire utilisable sur mobile** (iPhone 12+, Android 10+)
- [ ] **Performance acceptable** (< 2s chargement, < 500ms changement type/nature)

### 🛠️ Qualité technique

- [ ] **Aucune erreur console JS** (tous navigateurs cibles)
- [ ] **Aucun warning ESLint** (si configuré)
- [ ] **Code conforme PSR-12** (PHP) et style guide JS
- [ ] **PHPStan niveau 10 passé** (0 erreur)

### 🧪 Tests manuels

- [ ] **20 natures représentatives testées** (checklist ci-dessus)
- [ ] **Tous les types testés** (1-7)
- [ ] **Cas limites vérifiés** (champs vides, valeurs extrêmes, etc.)
- [ ] **Tests cross-browser** (Chrome, Firefox, Safari)

### 📚 Documentation

- [ ] **Architecture JS documentée** (`ARCHITECTURE_JS.md` à créer)
- [ ] **Guide ajout nouvelle interaction** (pour futurs développeurs)
- [ ] **Mapping legacy → migré complet** (ce fichier + `LEGACY_JS_INVENTORY.md`)

---

## 🚀 Procédure de test automatisée (script shell)

**Fichier :** `tests/manual/test-dossier-js.sh` (à créer)

```bash
#!/bin/bash
# Script de test semi-automatisé pour interactions JS dossier

echo "🧪 Tests interactions JavaScript - Formulaire dossier"
echo "======================================================"

# 1. Vérifier environnement
echo "1. Vérification environnement..."
curl -s http://localhost:7080/health > /dev/null && echo "✅ Serveur accessible" || exit 1

# 2. Créer dossiers de test via API
echo "2. Création dossiers de test..."
# TODO: Appeler API Symfony pour créer 20 dossiers de test (1 par nature représentative)

# 3. Lancer tests Playwright (si configuré)
echo "3. Tests automatisés Playwright..."
# npm run test:e2e

# 4. Checklist manuelle
echo "4. Tests manuels requis :"
echo "   - [ ] Test 1.1 : Changement type"
echo "   - [ ] Test 1.2 : Changement nature"
echo "   - [ ] Test 3.1 : Calendrier commission"
echo "   - [ ] Test 6.1 : Avis défavorable"
echo "   - [ ] Test 8.1 : Plat'AU"

echo ""
echo "📝 Résultats à documenter dans : docs/tech/dossier/TEST_RESULTS.md"
```

**Usage :**
```bash
chmod +x tests/manual/test-dossier-js.sh
./tests/manual/test-dossier-js.sh
```

---

## 📊 Métriques de succès

| Métrique | Objectif | Actuel | Statut |
|----------|----------|--------|--------|
| **Interactions migrées** | 40/40 (100%) | ~32/40 (80%) | 🟡 En cours |
| **Natures testées** | 20/62 | 0/62 | ❌ À démarrer |
| **Types testés** | 7/7 | 0/7 | ❌ À démarrer |
| **Erreurs console JS** | 0 | ? | ⚠️ Non vérifié |
| **Performance (changement type)** | < 500ms | ? | ⚠️ Non mesuré |
| **Performance (chargement page)** | < 2s | ? | ⚠️ Non mesuré |
| **Tests cross-browser** | 3/3 | 0/3 | ❌ À démarrer |

---

## 📅 Planning tests (estimation)

| Bloc | Temps estimé | Priorité | Dépendances |
|------|--------------|----------|-------------|
| **Bloc 1** | 4h | 🔴 Haute | Aucune |
| **Bloc 2** | 2h | 🟡 Moyenne | Routes AJAX backend |
| **Bloc 3** | 3h | 🔴 Haute | Routes calendrier backend |
| **Bloc 4** | 1h | 🟢 Basse | Aucune |
| **Bloc 5** | 2h | 🟡 Moyenne | Validation Symfony |
| **Bloc 6** | 2h | 🟡 Moyenne | Aucune |
| **Bloc 8** | 2h | 🟡 Moyenne | Aucune |
| **Bloc 9** | 1h | 🟢 Basse | Routes Changement backend (BLOQUÉ) |
| **Tests cross-browser** | 2h | 🟡 Moyenne | Tous blocs terminés |
| **Documentation résultats** | 1h | 🟢 Basse | Tous tests terminés |

**Total :** 20 heures de tests manuels

---

## 🔗 Références

- **Plan migration :** `/PLAN_MIGRATION_JS_DOSSIER.md`
- **Inventaire legacy :** `/docs/tech/dossier/LEGACY_JS_INVENTORY.md`
- **Analyse écarts :** `/docs/tech/dossier/MIGRATION_GAPS_ANALYSIS.md`
- **Mapping champs :** `/docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md`
- **Instructions édition :** `/.github/edition-dossier.md`

---

**Dernière mise à jour :** 2026-01-26  
**Auteur :** Documentation générée Phase 0.3  
**Statut :** ✅ Prêt pour tests manuels
