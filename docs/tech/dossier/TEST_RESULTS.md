# Résultats des tests - Interactions JavaScript dossier

**Date des tests :** _À compléter_  
**Testeur :** _À compléter_  
**Environnement :** http://localhost:7080  
**Navigateur(s) :** _À compléter (ex: Chrome 120, Firefox 115, Safari 16)_

---

## 📊 Résumé exécutif

| Métrique | Résultat | Objectif | Statut |
|----------|----------|----------|--------|
| **Tests réussis** | __/96 | 96 | ⏳ |
| **Tests échoués** | __ | 0 | ⏳ |
| **Blocs validés** | __/8 | 8 | ⏳ |
| **Erreurs console JS** | __ | 0 | ⏳ |
| **Performance (changement type)** | __ms | < 500ms | ⏳ |
| **Performance (chargement page)** | __s | < 2s | ⏳ |

**Statut global :** 🔴 Non démarré / 🟡 En cours / 🟢 Validé

---

## 🧪 Résultats détaillés par bloc

### 🟢 Bloc 1 : Visibilité conditionnelle des champs (Type & Nature)

**Fichier testé :** `public/js/dossier/form.js`

#### Test 1.1 - Changement de TYPE dossier

| Type | Champs visibles | Champs masqués | Statut | Notes |
|------|-----------------|----------------|--------|-------|
| Type 1 (Étude) | | | ⏳ | |
| Type 2 (Visite commission) | | | ⏳ | |
| Type 3 (Groupe visite) | | | ⏳ | |
| Type 4 (Réunion) | | | ⏳ | |
| Type 5 (Courrier) | | | ⏳ | |

**Observations :**
- _À compléter : transitions fluides ? erreurs console ?_

---

#### Test 1.2 - Changement de NATURE dossier

| Nature | ID | Champs conditionnels | Statut | Notes |
|--------|----|---------------------|--------|-------|
| PC | 1 | | ⏳ | |
| Dérogation | 3 | | ⏳ | |
| Levée prescriptions | 7 | | ⏳ | |
| Levée réserves | 19 | | ⏳ | |
| Déclaration préalable | 30 | | ⏳ | |

**Observations :**
- _Configuration `fieldsConfig` correcte ?_
- _Ordre d'affichage logique ?_

---

#### Test 1.3 - Interdépendance INCOMPLET ↔ AVIS/COMMISSION

| Scénario | Comportement attendu | Résultat | Statut | Notes |
|----------|---------------------|----------|--------|-------|
| Cocher "Incomplet" | Section avis masquée | | ⏳ | |
| Décocher "Incomplet" | Section avis réaffichée | | ⏳ | |
| Incomplet + Sauvegarder | Persistance état | | ⏳ | |
| Décocher avec avis défini | Avis préservé | | ⏳ | |

**Observations :**
- _Synchronisation temps réel OK ?_
- _Perte de données saisies ?_

**✅ Bloc 1 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🟡 Bloc 2 : Auto-complétion et recherches asynchrones

**Fichier testé :** `public/js/dossier/form.js`

#### Test 2.1 - Autocomplete Service Instructeur

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Focus champ | Dropdown TomSelect | | ⏳ | |
| Taper "Mar" | Filtrage local | | ⏳ | |
| Sélectionner "Marseille" | Champ rempli | | ⏳ | |
| Vider + re-sélectionner | Pas de duplication | | ⏳ | |

**Observations :**
- _TomSelect initialisé correctement ?_
- _Filtrage case-insensitive ?_
- _Mobile-friendly ?_

**⚠️ Note :** Autocomplete préventionnistes NON testé (AJAX non encore migré)

**✅ Bloc 2 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🔴 Bloc 3 : Calendrier commissions & visites

**Fichier testé :** `public/js/dossier/calendar-init.js`

#### Test 3.1 - Affichage/masquage calendrier COMMISSION

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Clic "Afficher calendrier" | Calendrier affiché | | ⏳ | |
| Événements chargés | Dates commission visibles | | ⏳ | |
| Clic sur date | Champ `dateCommission` rempli | | ⏳ | |
| Masquage automatique | Calendrier disparaît | | ⏳ | |

**Observations :**
- _AJAX `/calendrier-des-commissions/recupevenement` OK ?_
- _Format dates cohérent ?_
- _Drag & drop désactivé ?_

---

#### Test 3.2 - Affichage/masquage calendrier VISITE

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Clic "Afficher calendrier visite" | Calendrier affiché | | ⏳ | |
| Clic sur date | Champ `dateVisite` rempli | | ⏳ | |

**Observations :**
- _Indépendance calendrier commission/visite ?_

---

#### Test 3.3 - Synchronisation changement COMMISSION

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Changer commission | Dates vidées automatiquement | | ⏳ | |
| Afficher nouveau calendrier | Nouvelles dates affichées | | ⏳ | |

**Observations :**
- _Synchronisation `commission.js` ↔ `calendar-init.js` ?_

**✅ Bloc 3 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🟢 Bloc 4 : Documents d'urbanisme

**Fichier testé :** `public/js/dossier/form.js`

#### Test 4.1 - Auto-préfixe documents

| Type doc | Valeur saisie | Résultat attendu | Résultat | Statut | Notes |
|----------|---------------|------------------|----------|--------|-------|
| PC | `2023001` | `PC2023001` | | ⏳ | |
| AT | `2024042` | `AT2024042` | | ⏳ | |
| DP | `789` | `DP789` | | ⏳ | |

**Observations :**
- _Préfixe ajouté au blur ?_
- _Pas de duplication (PCPC...) ?_

---

#### Test 4.2 - Ajout dynamique document

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Ajouter 3 documents | 3 blocs affichés | | ⏳ | |
| Index incrémentés | Pas de conflit IDs | | ⏳ | |

---

#### Test 4.3 - Suppression document

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Supprimer document 2/3 | Bloc disparaît | | ⏳ | |
| Sauvegarder + recharger | 2 documents en base | | ⏳ | |

**✅ Bloc 4 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🟡 Bloc 5 : Validation et sauvegarde formulaire

**Fichier testé :** `public/js/dossier/form.js` + contrôleur Symfony

#### Test 5.1 - Boutons "Aujourd'hui"

| Champ | Bouton visible | Date correcte | Statut | Notes |
|-------|----------------|---------------|--------|-------|
| Date insert | | | ⏳ | |
| Date dépôt | | | ⏳ | |
| Date commission | | | ⏳ | |
| Date visite | | | ⏳ | |
| Date PEC | | | ⏳ | |
| Échéancier travaux | | | ⏳ | |

**Observations :**
- _Format YYYY-MM-DD ?_
- _Boutons masqués si champs masqués ?_

---

#### Test 5.2 - Validation côté serveur

| Données soumises | Validation attendue | Résultat | Statut | Notes |
|------------------|---------------------|----------|--------|-------|
| Objet vide | ❌ Erreur | | ⏳ | |
| Type non défini | ❌ Erreur | | ⏳ | |
| Nature non définie | ❌ Erreur | | ⏳ | |
| Tout valide | ✅ Succès | | ⏳ | |

**Observations :**
- _Erreurs affichées clairement ?_
- _Champs en erreur mis en évidence ?_
- _Pas de perte de données ?_

---

#### Test 5.3 - Sauvegarde et redirection

| Action | Résultat attendu | Résultat | Statut | Notes |
|--------|------------------|----------|--------|-------|
| Clic "Enregistrer" | POST vers `/dossier/{id}/edit` | | ⏳ | |
| Backend OK | Redirection vers `/dossier/{id}` | | ⏳ | |
| Flash message | "Dossier modifié avec succès" | | ⏳ | |
| Données persistées | Vérification en base OK | | ⏳ | |

**✅ Bloc 5 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🟡 Bloc 6 : Avis & Dérogations

**Fichier testé :** `public/js/dossier/form.js`

#### Test 6.1 - Affichage conditionnel champs avis

| Avis | ID | Champs supplémentaires | Statut | Notes |
|------|----|------------------------|--------|-------|
| Aucun | 0 | Aucun | ⏳ | |
| Favorable | 1 | Aucun | ⏳ | |
| Défavorable | 2 | Facteur + Échéancier | ⏳ | |
| Avec réserves | 3 | Selon config | ⏳ | |

**Observations :**
- _Affichage instantané (< 100ms) ?_
- _Champs requis si avis défavorable ?_

---

#### Test 6.2 - Masquage avis si INCOMPLET

| État dossier | Section avis affichée | Résultat | Statut | Notes |
|--------------|----------------------|----------|--------|-------|
| Complet | ✅ Oui | | ⏳ | |
| Incomplet | ❌ Non (classe `hideavis`) | | ⏳ | |
| Incomplet → Complet | ✅ Réapparaît | | ⏳ | |

**Observations :**
- _Synchronisation avec `gestion-incomplet.js` ?_
- _Données avis préservées ?_

---

#### Test 6.3 - Labels avis dynamiques

| Type | Nature | Label attendu | Résultat | Statut | Notes |
|------|--------|---------------|----------|--------|-------|
| 1 | 1 (PC) | "Avis de la commission" | | ⏳ | |
| 2 | 20 (Réception) | "Favorable à réception travaux" | | ⏳ | |

**✅ Bloc 6 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🔴 Bloc 8 : Intégrations Plat'AU

**Fichier testé :** `public/js/dossier/platau.js`

#### Test 8.1 - Affichage conditionnel bloc Plat'AU

| Condition | Bloc affiché | Résultat | Statut | Notes |
|-----------|--------------|----------|--------|-------|
| Dossier incomplet | ✅ Oui | | ⏳ | |
| Complet + Avis défini | ✅ Oui | | ⏳ | |
| Complet + Aucun avis | ❌ Non | | ⏳ | |
| Dossier NON Plat'AU | ❌ Non | | ⏳ | |

**Observations :**
- _Fonction `isDossierPlatau()` OK ?_
- _Synchronisation incomplet + avis ?_

---

#### Test 8.2 - Badge alerte Plat'AU

| Pièces jointes | Badge affiché | Action clic | Résultat | Statut | Notes |
|----------------|---------------|-------------|----------|--------|-------|
| Vides | ✅ Oui (danger) | Scroll vers champ | | ⏳ | |
| Renseignées | ❌ Non | - | | ⏳ | |

**Observations :**
- _Scroll smooth ?_
- _Focus automatique champ ?_

**✅ Bloc 8 validé :** ⏳ Non / 🟡 Partiellement / 🟢 Oui

---

### 🟢 Bloc 9 : Alertes et modals

**Fichier testé :** `public/js/dossier/modals.js` (à créer)

#### Test 9.1 - Modal confirmation suppression

**⚠️ Note :** Fonctionnalité sur page affichage (hors scope édition)

**Statut :** ⏭️ Non applicable

---

#### Test 9.2 - Modal alerte établissement

**⚠️ Statut :** 🔴 BLOQUÉ - Routes backend `/changement/*` non migrées

**Statut :** ⏭️ Bloqué

**✅ Bloc 9 validé :** ⏭️ Bloqué / Non testé

---

## 🐛 Bugs identifiés

| ID | Bloc | Description | Gravité | Statut | Notes |
|----|------|-------------|---------|--------|-------|
| #1 | | | 🔴🟡🟢 | ⏳ | |
| #2 | | | 🔴🟡🟢 | ⏳ | |

**Gravité :** 🔴 Critique (bloquant) / 🟡 Majeur (gênant) / 🟢 Mineur (cosmétique)

---

## 🌐 Tests cross-browser

| Navigateur | Version | Statut | Erreurs console | Notes |
|------------|---------|--------|-----------------|-------|
| Chrome | | ⏳ | | |
| Firefox | | ⏳ | | |
| Safari | | ⏳ | | |
| Edge | | ⏳ | | |

---

## ⚡ Tests de performance

| Métrique | Mesure | Objectif | Statut | Notes |
|----------|--------|----------|--------|-------|
| Chargement page edit | __s | < 2s | ⏳ | Mesuré avec DevTools Network |
| Changement type | __ms | < 500ms | ⏳ | Mesuré avec Performance |
| Changement nature | __ms | < 500ms | ⏳ | |
| Affichage calendrier | __ms | < 1s | ⏳ | |
| Utilisation 30min continue | Leak ? | Non | ⏳ | Memory profiler |

**Outils utilisés :**
- Chrome DevTools > Performance
- Chrome DevTools > Memory (heap snapshots)
- Lighthouse (optionnel)

---

## 📝 Observations générales

**Points positifs :**
- _À compléter_

**Points d'amélioration :**
- _À compléter_

**Recommandations :**
- _À compléter_

---

## ✅ Checklist validation finale

- [ ] Toutes les interactions legacy reproduites (référence : `LEGACY_JS_INVENTORY.md`)
- [ ] Aucune régression détectée (comparaison side-by-side)
- [ ] Formulaire utilisable sur mobile (testé)
- [ ] Performance acceptable (< 2s chargement, < 500ms interactions)
- [ ] Aucune erreur console JS (tous navigateurs)
- [ ] Code conforme standards (ESLint passé)
- [ ] PHPStan niveau 10 passé (si PHP modifié)
- [ ] Documentation à jour (REPRENDRE_ICI.md)

---

## 📊 Conclusion

**Statut global :** 🔴 Échec / 🟡 Partiel / 🟢 Réussi

**Taux de réussite :** __% (__/96 tests passés)

**Date de validation :** _À compléter_

**Validé par :** _À compléter_

**Prochaines étapes :**
1. _À compléter_
2. _À compléter_

---

**Dernière mise à jour :** _À compléter_  
**Fichier :** `docs/tech/dossier/TEST_RESULTS.md`
