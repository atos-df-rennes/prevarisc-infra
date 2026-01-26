# Inventaire JavaScript Legacy - Formulaire Dossier

**Date :** 2026-01-23  
**Fichier source :** `prevarisc/application/views/scripts/dossier/index.phtml`  
**Lignes totales :** 2567  
**Event listeners :** ~40  
**Appels AJAX :** ~16  

---

## 📊 Vue d'ensemble

Ce document inventorie **TOUTES** les interactions JavaScript présentes dans le formulaire legacy d'édition de dossier, avec numéros de ligne, description, et statut de migration.

**Légende :**
- ✅ Migré et testé
- 🟡 Partiellement migré
- ❌ Non migré
- ⚠️ Non applicable (contexte différent entre legacy et Symfony)

---

## 🎯 Interactions par catégorie

### 1. INITIALISATION ET CONFIGURATION (Lignes 1-150)

#### 1.1 Modal Alerte Établissement
**Lignes :** 3-70  
**Event :** `.alerte-link` click  
**Description :**  
- Ouvre modal avec TinyMCE pour rédiger une alerte email
- AJAX GET `/changement/alerteform` → charge formulaire
- AJAX POST `/changement/sendmailalerte` → envoie email
- Destroy TinyMCE à la fermeture du modal

**Statut :** ❌ Non migré  
**Fichier cible :** `modals.js`  
**Bloquant :** 🔴 Routes backend `/changement/*` manquantes en Symfony  
**Note :** Fonctionnalité encore utilisée

---

#### 1.2 Modal Suppression Dossier
**Lignes :** 73-77  
**Event :** `#suppressionDossier` click  
**Description :**  
- Ouvre modal Bootstrap de confirmation
- Modal ID : `#confirm-modal`

**Statut :** ❌ Non migré  
**Fichier cible :** `modals.js`  
**Dépendances :** Aucune

**Note:** Suppression faisable uniquement sur affichage dossier, non applicable sur édition.

---

#### 1.3 Autocomplete Préventionnistes
**Lignes :** 81-130  
**Event :** `#preventionnistes-autocomplete` typeahead  
**Description :**  
- Recherche utilisateurs avec fonction "Préventionniste" (ID 13)
- AJAX POST `/api/1.0/search/users` avec params:
  - `name`: query
  - `fonctions`: 13
  - `limit`: 100
- Format résultat : `<span data-id="UID">NOM Prénom</span>`
- Click sur suggestion → remplit champ + trigger change

**Statut :** ❌ Non migré  
**Fichier cible :** `autocomplete.js`  
**Dépendances :** API `/api/1.0/search/users` (existe en Symfony ?)  
**Note :** Remplacer typeahead par TomSelect (décision architecturale)

**Note:** Déjà migré. N'utilise pas AJAX : pas forcément problématique étant donné le peu d'éléments (50 préventionnistes maximum)

---

#### 1.4 Autocomplete Ville Service Instructeur
**Lignes :** 130-148  
**Event :** `#servInstVille` autocomplete  
**Description :**  
- jQuery UI autocomplete sur champ ville
- Route backend non visible dans extrait (à identifier)
- Remplit champ ville après sélection

**Statut :** ❌ Non migré  
**Fichier cible :** `autocomplete.js`  
**Dépendances :** Route backend autocomplete ville (à identifier)  
**Note :** Remplacer par TomSelect

**Note:** Déjà migré. Fonctionnement modifié pour utiliser un TomSelect unique et améliorer l'UX.

---

#### 1.5 Changement Commission → Vider Dates
**Lignes :** 150-169  
**Event :** `#commissionSelect` change  
**Description :**  
- Si nouvelle commission sélectionnée → vide `dateVisite` et `dateCommission`
- Trigger click sur `.changeCommission` (listener ligne 170)
- Empêche modification involontaire de dates lors du changement

**Statut :** 🟡 Partiellement migré  
**Fichier existant :** `commission.js`  
**TODO :** Vérifier si logique de vidage des dates est présente

---

#### 1.6 Bouton "Changer Commission" (cache calendrier)
**Lignes :** 170-192  
**Event :** `.changeCommission` click  
**Description :**  
- Masque calendriers (commission + visite) si affichés
- Réinitialise dates si changement confirmé

**Statut :** 🟡 Partiellement migré  
**Fichier existant :** `commission.js`  
**TODO :** Vérifier conformité avec legacy

---

### 2. GESTION CALENDRIER (Lignes 194-850)

#### 2.1 Afficher Calendrier
**Lignes :** 194-220  
**Event :** `.showCalendar` click  
**Description :**  
- Cache le bouton "Afficher calendrier"
- Affiche conteneur calendrier
- Initialise FullCalendar via `$.fn.afficheCalendar()` (ligne 400+)

**Statut :** ✅ Migré  
**Fichier :** `calendar-init.js`  
**Note :** Vérifier conformité avec legacy (ligne 400-850)

---

#### 2.2 Masquer Calendrier
**Lignes :** 221-240  
**Event :** `.hideCalendar` click  
**Description :**  
- Masque conteneur calendrier
- Réaffiche bouton "Afficher calendrier"
- Détruit instance FullCalendar

**Statut :** ✅ Migré  
**Fichier :** `calendar-init.js`  
**Note :** Vérifier destruction propre de l'instance

---

#### 2.3 Plugin FullCalendar (jQuery extension)
**Lignes :** 400-850 (~450 lignes)  
**Description :**  
- `$.fn.afficheCalendar(options)` - Plugin custom FullCalendar
- Chargement événements via AJAX `/calendrier-des-commissions/recupevenement`
- Sélection date → remplit champ + masque calendrier
- Drag & drop d'événements (si autorisé)
- Resize événements
- Click événement → modal détails (AJAX `/dialogcomm`)
- Gestion vue mensuelle par défaut
- Désactivation weekends (selectable: false le weekend)

**Fonctionnalités clés :**
- `select(start, end)` : Sélection plage → remplir date
- `eventDrop()` : Drag événement → save AJAX
- `eventResize()` : Resize → save AJAX
- `eventClick()` : Click → modal détails
- `loading()` : Spinner pendant chargement

**Statut :** 🟡 Partiellement migré  
**Fichier :** `calendar-init.js`  
**TODO :** Comparer ligne par ligne avec legacy pour vérifier conformité  
**Routes backend :**  
  - `/calendrier-des-commissions/recupevenement` (GET événements)
  - `/calendrier-des-commissions/adddates` (POST nouvelle date)
  - `/calendrier-des-commissions/dialogcomm` (GET modal détails)

**Note:** Routes Symfony pouvant être préfixées pour éviter conflits avec d'autres utilisations.

---

### 3. GESTION TYPE & NATURE (Lignes 242-290)

#### 3.1 Changement Type Dossier
**Lignes :** 242-288  
**Event :** `#TYPE_DOSSIER` change  
**Description :**  
- Charge natures disponibles via AJAX `/dossier/shownature`
- Masque/affiche sections selon type :
  - Type 1 (Étude) : Affiche commission, masque visite
  - Type 2 (Visite) : Masque commission, affiche visite
  - Type 3 (Groupe de visite) : Masque commission, affiche visite
  - Type 4 (Autre) : Masque commission et visite
- Vide champs nature + liés
- Trigger `$.fn.recupNatures()` et `$.fn.afficheChamps()`

**Statut :** 🟡 Partiellement migré  
**Fichier :** `form.js`  
**TODO :**  
  - Vérifier conformité affichage/masquage sections
  - S'assurer AJAX `/dossier/shownature` fonctionne
  - Tester avec types 1-4

**Note:** AJAX `/dossier/shownature` remplacé par une configuration serveur envoyé au Javascript.

---

### 4. GESTION INCOMPLET (Lignes 290-360)

#### 4.1 Clic Checkbox "Incomplet"
**Lignes :** 290-318  
**Event :** `.incomplet` click  
**Description :**  
- Si coché : masque section avis, affiche formulaire documents manquants
- AJAX `/dossier/formdocmanquant` → charge formulaire
- Ajoute classe `hideavis` pour masquer avis commission

**Statut :** ✅ Migré  
**Fichier :** `gestion-incomplet.js`  
**Note :** Architecture moderne avec Symfony Collection Forms.

---

#### 4.2 Valider Réception Document
**Lignes :** 319-337  
**Event :** `.validDocManquant` click  
**Description :**  
- Passe dossier de "Incomplet" à "Complet"
- Masque formulaire documents manquants
- Réaffiche section avis

**Statut :** ✅ Migré  
**Fichier :** `gestion-incomplet.js`  
**Note :** Workflow complet implémenté

---

#### 4.3 Annuler Date Réception
**Lignes :** 338-349  
**Event :** `.cancelDateRecep` click  
**Description :**  
- Vide champ date de réception
- Permet de modifier après validation

**Statut :** ✅ Migré  
**Fichier :** `gestion-incomplet.js`  
**Note :** Bouton "Modifier" remplace cette logique

---

#### 4.4 Annuler Incomplet (Retour Complet)
**Lignes :** 350-358  
**Event :** `.cancelIncomplet` click  
**Description :**  
- Supprime le bloc document manquant ajouté
- Retourne à l'état "Complet"
- Réaffiche section avis

**Statut :** ✅ Migré  
**Fichier :** `gestion-incomplet.js`  
**Note :** Délégation d'événements correctement implémentée

---

#### 4.5 Masquage Avis si Incomplet
**Lignes :** 359-398  
**Event :** `.hideavis` click  
**Description :**  
- Classe ajoutée au radio "Incomplet"
- Masque section avis commission si dossier incomplet
- Logique : dossier incomplet → pas d'avis possible

**Statut :** 🟡 Partiellement migré  
**Fichier :** `form.js` (à implémenter)  
**TODO :** Lier avec `gestion-incomplet.js` pour synchronisation

---

### 5. PLUGIN FULLCALENDAR (détaillé)

**Note :** Voir section 2.3 pour inventaire détaillé du plugin FullCalendar (lignes 400-850)

---

### 6. DOCUMENTS URBANISME (Lignes 846-914)

#### 6.1 Suppression Document Urbanisme
**Lignes :** 846-912  
**Event :** `.suppression` click  
**Description :**  
- Supprime ligne document urbanisme du DOM
- Utilise `$(this).parent().remove()`
- Inline onclick aussi utilisé dans template : `<a onclick='$(this).parent().remove(); return false;'>`

**Statut :** 🟡 À vérifier  
**Fichier :** `form.js` (gestion Symfony Collection Forms)  
**TODO :**  
  - Vérifier si suppression fonctionne avec Symfony prototype
  - Remplacer inline onclick par délégation d'événements

---

### 7. BOUTONS "AUJOURD'HUI" (Lignes 915-938)

#### 7.1 Remplir Date du Jour
**Lignes :** 915-938  
**Event :** `.today` click  
**Description :**  
- Trouve input date adjacent (`.prev()`)
- Remplit avec date du jour au format YYYY-MM-DD
- Utilisé pour : dateInsert, dateVisite, dateCommission, echeancierTravaux, etc.

**Statut :** 🟡 Partiellement migré  
**Fichier :** `form.js`  
**TODO :**  
  - Vérifier présence boutons pour TOUS les champs date
  - S'assurer format date correct (YYYY-MM-DD)
  - Utiliser délégation d'événements

---

### 8. SAUVEGARDE FORMULAIRE (Lignes 1039-1084)

#### 8.1 Modification Dossier (AJAX Save)
**Lignes :** 1039-1084  
**Event :** `#modificationDossier` click  
**Description :**  
- Validation client via `verifDossier()` (fonction custom)
- AJAX POST `/dossier/save` ou `/dossier/savenew`
- Sérialise formulaire avec `.serialize()`
- Affiche bouton "Annuler modification" pendant save
- Gère réponse : succès → reload, erreur → message

**Statut :** ⚠️ Non applicable  
**Note :** Symfony utilise POST classique, pas AJAX (décision architecturale validée)  
**Validation côté serveur** : Constraints Symfony + handleRequest()  
**Validation client (UX)** : À implémenter dans `validation.js` (optionnel)

---

### 9. INTÉGRATION PLAT'AU (Lignes 1086-1112)

#### 9.1 Affichage Bloc Pièces Jointes Plat'AU
**Lignes :** 1086-1112  
**Events namespaced `.platau` :**  
- `.incomplet` click → affiche bloc PJ si incomplet
- `.cancelIncomplet` click → masque bloc PJ
- `#AVIS_DOSSIER_COMMISSION` change → affiche/masque selon avis défini
- `#select-platau` click → scroll vers champ PJ

**Description :**  
- Fonction `isDossierPlatau()` : détecte si dossier lié à Plat'AU
- Fonction `gestionAffichageInfosPlatau()` : gère affichage conditionnel
- Conditions affichage :
  - Dossier incomplet OU
  - Avis commission défini (value != 0 && != '')
- Badge alerte si PJ manquantes

**Statut :** ✅ Migré  
**Fichier :** `platau.js`  
**Note :** Récemment modifié, à vérifier conformité complète

---

### 10. FONCTIONS CUSTOM (à documenter)

#### 10.1 `verifDossier()`
**Description :** Validation formulaire côté client  
**Lignes :** Non identifiées (probablement après ligne 1112)  
**Statut :** ⚠️ Non applicable (validation Symfony prioritaire)

#### 10.2 `$.fn.recupNatures()`
**Description :** Récupère liste des natures sélectionnées  
**Statut :** ❌ À identifier

#### 10.3 `$.fn.cacherNatureDuSelect()`
**Description :** Masque natures déjà sélectionnées dans dropdown  
**Statut :** ❌ À identifier

#### 10.4 `$.fn.afficheChamps()`
**Description :** Affiche champs selon nature sélectionnée (AJAX `/dossier/showchamps`)  
**Statut :** ❌ À identifier

---

## 📋 Récapitulatif Migration

### Blocs Migrés ✅
- Documents manquants (incomplet) - `gestion-incomplet.js`
- Calendrier (partiel) - `calendar-init.js`
- Plat'AU (partiel) - `platau.js`
- Commission - `commission.js`

### Blocs Non Migrés ❌
- Modal alerte établissement (🔴 bloqué backend)
- Modal suppression dossier
- Autocomplete préventionnistes => Normalement déjà fait
- Autocomplete ville => Normalement déjà fait
- Documents urbanisme (à vérifier) => Normalement déjà fait
- Boutons "Aujourd'hui" (partiel)
- Visibilité conditionnelle TYPE/NATURE (partiel)
- Avis défavorable → facteur + échéancier

### Blocs Non Applicables ⚠️
- Sauvegarde AJAX (POST classique Symfony)
- Validation client complexe (Symfony Constraints prioritaires)

---

## 🎯 Prochaines étapes

1. ✅ **Phase 0.1** - Inventaire complété
2. **Phase 0.2** - Identifier lignes manquantes (fonctions custom après ligne 1112)
3. **Phase 0.3** - Préparer environnement de test
4. **Bloc 1** - Compléter visibilité conditionnelle TYPE/NATURE
5. **Bloc 2** - Migrer autocomplete (TomSelect)

---

**Dernière mise à jour :** 2026-01-23  
**Statut :** 📝 Inventaire Phase 0.1 terminé - Fonctions custom à documenter
