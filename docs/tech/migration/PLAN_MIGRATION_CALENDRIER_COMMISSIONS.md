# Plan de migration — Calendrier des commissions

> **Module :** `CalendrierDesCommissionsController` (25 actions)
> **Stratégie :** Port direct — reproduire le comportement legacy
> **Stack cible :** PHP 7.1.33 / Symfony 4.4 / Doctrine 2.14 / Twig 2 / Bootstrap 3
> **Date :** Juillet 2025

---

## Table des matières

1. [État actuel](#1-état-actuel)
2. [Inventaire des 25 actions legacy](#2-inventaire-des-25-actions-legacy)
3. [Découpage en phases](#3-découpage-en-phases)
4. [Phase A — Affichage du calendrier (prioritaire)](#4-phase-a--affichage-du-calendrier)
5. [Phase B — Gestion ODJ](#5-phase-b--gestion-odj)
6. [Phase C — Génération de documents](#6-phase-c--génération-de-documents)
7. [Phase D — Export Outlook](#7-phase-d--export-outlook)
8. [Dépendances et risques](#8-dépendances-et-risques)
9. [Estimation effort par phase](#9-estimation-effort-par-phase)
10. [Recommandation](#10-recommandation)

---

## 1. État actuel

### Code déjà migré (5 routes dans `CalendrierDesCommissionsController.php`)

| Route Symfony | Action legacy | Statut |
|---|---|---|
| `calendrier_des_commissions_recuperer_evenements` (GET) | `recupevenementAction` | ✅ Migré |
| `calendrier_des_commissions_recuperer_dates_liees` (GET) | `recupdatelieeAction` | ✅ Migré |
| `calendrier_des_commissions_redimensionner_date` (POST) | `resizecommissiondateAction` | ✅ Migré |
| `calendrier_des_commissions_deplacer_date` (POST) | `deplacecommissiondateAction` | ✅ Migré |
| `calendrier_des_commissions_creer_date` (GET/POST) | `adddatesAction` + `dialogcommAction (newComm)` | ✅ Migré |

### Services Symfony existants

| Service | Rôle |
|---|---|
| `CalendarService` | Parsing dates, getEvents, updateHeureFin, deplacerDateCommission, sauvegarderDatesCommission |
| `CommissionDatesTransformer` | Transforme DTO → entités DateCommission (gère la périodicité) |
| `CommissionDocumentBuilder` | Assemblage de documents commission (ODT) |
| `CommissionDocumentCrService` | Génération comptes rendus |
| `CommissionMembreCourrierService` | Gestion courriers membres |
| `CommissionFilesystem` | Gestion fichiers commission |
| `AbstractOdtDocumentService` | Base pour la génération de documents ODT |
| `CommissionManagerService` | Gestion administrative des commissions |
| `CommissionRegleCompetenceService` | Règles de compétence |
| `DateCommission` (service) | Service dates de commission |

### Repositories existants

`DateCommissionRepository`, `CommissionRepository`, `CommissionTypeEvenementRepository`, `CommissionMembreRepository`, `CommissionTypeRepository`, `CommissionRegleRepository`

### Templates Twig existants

- `calendrier_des_commissions/form_date.html.twig` — formulaire de création de date
- `calendrier_des_commissions/_modal_creation_date.html.twig` — modale création

### FormTypes existants

- `CommissionDatesType` — formulaire création de dates
- `DateCommissionItemType` — sous-formulaire d'un item date

---

## 2. Inventaire des 25 actions legacy

### Légende statut

- ✅ = Déjà migré côté Symfony
- ❌ = À migrer

| # | Action legacy | Type | Résumé | Entités | Statut | Phase |
|---|---|---|---|---|---|---|
| 1 | `indexAction` | GET (page) | Page principale : charge commissions, types, ACL ; affiche FullCalendar | Commission, CommissionType | ❌ | **A** |
| 2 | `recupevenementAction` | GET (AJAX/JSON) | Récupère événements calendrier entre 2 dates, filtrage par commission/type | DateCommission | ✅ | A |
| 3 | `recupdatelieeAction` | GET (AJAX/JSON) | Récupère les dates liées à une date de commission | DateCommission | ✅ | A |
| 4 | `commissionselectionAction` | GET (AJAX/JSON) | Autocomplete : recherche commissions par libellé (`?q=`) | Commission | ❌ | **A** |
| 5 | `dialogcommAction` | POST (AJAX/HTML) | Tooltip/dialogue multi-cas : edit, newComm, addDateN, libelleCom, valid_libelleCom, annule_libelleCom, typeCom, valid_typeCom, annule_typeCom, dateComm, valid_dateCom, annule_dateCom, supp_dateCom, addDateS, makeDefaut | DateCommission, CommissionTypeEvenement | ❌ partiellement (newComm migré via creerDate) | **A** |
| 6 | `adddatecommAction` | POST (AJAX/HTML) | Affiche ligne date supplémentaire dans dialogue | — | ❌ | **A** |
| 7 | `adddatedialogcommAction` | POST (AJAX) | Confirme ajout date (echo "date ajoutée") | — | ❌ | **A** |
| 8 | `adddatesAction` | POST (AJAX/JSON) | Sauvegarde les dates dans la BD (avec/sans périodicité) | DateCommission | ✅ (via `creerDate`) | A |
| 9 | `deplacecommissiondateAction` | POST (AJAX) | Déplace une date de commission (date + heures) | DateCommission | ✅ | A |
| 10 | `resizecommissiondateAction` | POST (AJAX) | Modifie l'heure de fin d'un événement | DateCommission | ✅ | A |
| 11 | `alertsuppressionAction` | POST (AJAX/HTML) | Affiche la modale de confirmation de suppression | — | ❌ | **A** |
| 12 | `validsuppressionAction` | POST (AJAX) | Supprime date, dossier affectations, PJ physiques et BD | DateCommission, DossierAffectation, DateCommissionPj, Dossier | ❌ | **A** |
| 13 | `gestionodjAction` | GET (page) | Page ODJ : calendrier agendaDay, dossiers affectés/non affectés, drag-drop | DateCommission, Commission, DossierAffectation, Dossier, Etablissement | ❌ | **B** |
| 14 | `recupevenementodjAction` | GET (AJAX/JSON) | Récupère événements ODJ (dossiers affectés avec heures) | DossierAffectation, Dossier, Etablissement | ❌ | **B** |
| 15 | `affectedossodjAction` | POST (AJAX/JSON) | Affecte un dossier à un créneau horaire (drag-drop) | DossierAffectation, Dossier | ❌ | **B** |
| 16 | `resizeodjAction` | POST (AJAX) | Modifie l'heure de fin d'un dossier dans l'ODJ | DossierAffectation | ❌ | **B** |
| 17 | `dropodjAction` | POST (AJAX) | Déplace un dossier dans le calendrier ODJ (modifie heures début/fin) | DossierAffectation | ❌ | **B** |
| 18 | `gestionheuresAction` | POST (AJAX) | Active/désactive la gestion des heures pour une date | DateCommission, DossierAffectation | ❌ | **B** |
| 19 | `changementordreAction` | POST (AJAX) | Réordonne les dossiers (mode sans heures, sortable) | DossierAffectation | ❌ | **B** |
| 20 | `generationconvocAction` | POST (AJAX/HTML) | Génère convocations ODT par membre et commune | DateCommission, Commission, CommissionMembre, Dossier, Etablissement, DossierDocUrba, AdresseCommune | ❌ | **C** |
| 21 | `generationodjAction` | POST (AJAX/HTML) | Génère l'ordre du jour ODT | DateCommission, Commission, CommissionMembre, Dossier, Etablissement | ❌ | **C** |
| 22 | `generationpvAction` | POST (AJAX/HTML) | Génère le PV ODT (avec prescriptions) | DateCommission, Commission, CommissionMembre, Dossier, Etablissement, Prescriptions | ❌ | **C** |
| 23 | `generationcompterenduAction` | POST (AJAX/HTML) | Génère le compte rendu ODT | DateCommission, Commission, CommissionMembre, Dossier, Etablissement | ❌ | **C** |
| 24 | `exportoutlookAction` | GET (ICS) | Export ICS d'une seule date de commission | DateCommission, Commission | ❌ | **D** |
| 25 | `exportoutlookmoisAction` | GET (ICS) | Export ICS de toutes les dates d'un mois pour une commission | DateCommission, Commission, DossierAffectation | ❌ | **D** |

### Décompte

| Phase | Actions total | Déjà migrées | Restantes |
|---|---|---|---|
| A — Calendrier | 12 | 5 | **7** |
| B — ODJ | 7 | 0 | **7** |
| C — Génération docs | 4 | 0 | **4** |
| D — Export Outlook | 2 | 0 | **2** |
| **Total** | **25** | **5** | **20** |

---

## 3. Découpage en phases

```
Phase A ──────────────┐
  Page calendrier      │
  Dialogue édition     │  aucune dépendance entre A et D
  Suppression          │
                       │
Phase D ──────────────┘  (peut démarrer en parallèle)
  Export Outlook

Phase B ──────────────── (dépend de A : la page ODJ est accessible depuis le dialogue de A)
  Gestion ODJ
  Drag-drop dossiers
  Gestion heures/ordre

Phase C ──────────────── (dépend de B : les boutons de génération sont dans la page ODJ)
  Convocations
  Ordre du jour PDF
  PV
  Compte rendu
```

---

## 4. Phase A — Affichage du calendrier

### 4.1 Analyse de l'approche par commission dans l'URL

**Question posée :** Faut-il des routes dédiées `/calendrier-des-commissions/{id}` et `/calendrier-des-commissions` ?

**Réponse après analyse du JS legacy :**

Le legacy fonctionne avec une **seule page** `/calendrier-des-commissions` (action `indexAction`). La sélection de commission se fait **côté client** via un clic sur la navigation latérale qui appelle `afficheCalendar(idCommission, 'calendrierComm')`. Le paramètre optionnel `?idComm=X` dans l'URL permet un accès direct à une commission (utilisé par les liens entrants depuis d'autres pages).

**Recommandation : Garder une route unique avec paramètre optionnel.**

```
/calendrier-des-commissions          → Toutes les commissions
/calendrier-des-commissions?idComm=5 → Pré-sélection commission #5
```

**Raisons :**
1. Le JS FullCalendar existant est conçu pour fonctionner avec un `idComm` JS passé dynamiquement aux requêtes AJAX
2. Modifier en `/calendrier-des-commissions/{id}` nécessiterait un rechargement de page à chaque changement de commission, ce qui est contraire au comportement legacy (navigation SPA-like)
3. Le paramètre `?idComm=X` est déjà utilisé par d'autres pages pour pointer vers une commission spécifique (ex: depuis la page ODJ)

### 4.2 Routes Symfony à créer

| Route | URL | Méthode | Rôle | ACL legacy |
|---|---|---|---|---|
| `calendrier_des_commissions_index` | `/calendrier-des-commissions` | GET | Page principale avec FullCalendar | Accès authentifié ; `is_admin` = `gestion_parametrages/gestion_commissions` ; `is_view_all` = `commission/calendar_view_all` |
| `calendrier_des_commissions_dialog_edit` | `/calendrier-des-commissions/dialogcomm` | POST (AJAX) | Dialogue d'édition d'un événement existant (libellé, type, dates, ajout/suppression dates liées) | Accès authentifié |
| `calendrier_des_commissions_alertsuppression` | `/calendrier-des-commissions/alertsuppression` | POST (AJAX) | Fragment HTML de confirmation de suppression | Accès authentifié |
| `calendrier_des_commissions_validsuppression` | `/calendrier-des-commissions/validsuppression` | POST (AJAX) | Exécute la suppression d'une date et de toutes ses dépendances | Accès authentifié |
| `calendrier_des_commissions_commissionselection` | `/calendrier-des-commissions/commissionselection` | GET (AJAX/JSON) | Autocomplete commission par libellé | Accès authentifié |

> **Note :** Les 5 routes déjà migrées (recupevenement, recupdateliee, redimensionner-date, deplacer-date, creer-date) restent inchangées.

### 4.3 Le cas complexe : `dialogcommAction`

L'action `dialogcommAction` est un **mega-switch** sur le paramètre `do` avec 16 cas différents. Côté legacy, elle retourne du **HTML brut** injecté dans un dialogue jQuery UI via AJAX.

**Stratégie de migration :**

Le cas `newComm` (création) est déjà migré via `creerDate`. Les cas restants sont tous liés à l'**édition** d'un événement existant :

| Cas `do=` | Résumé | Type retour |
|---|---|---|
| `edit` | Affichage initial du dialogue d'édition | HTML (Twig fragment) |
| `libelleCom` | Formulaire inline édition libellé | HTML fragment |
| `valid_libelleCom` | Sauvegarde libellé → affiche libellé | HTML fragment |
| `annule_libelleCom` | Annule → réaffiche libellé | HTML fragment |
| `typeCom` | Formulaire inline édition type | HTML fragment |
| `valid_typeCom` | Sauvegarde type → affiche type | HTML fragment |
| `annule_typeCom` | Annule → réaffiche type | HTML fragment |
| `dateComm` | Formulaire inline édition date | HTML fragment |
| `valid_dateCom` | Sauvegarde date/heures → affiche date | HTML fragment |
| `annule_dateCom` | Annule → réaffiche date | HTML fragment |
| `supp_dateCom` | Supprime une date liée | HTML fragment (vide si master) |
| `addDateN` | Ajoute ligne date (mode création) | HTML fragment |
| `addDateS` | Insère date liée en BD + affiche | HTML fragment |
| `makeDefaut` | Change la date maître d'un groupe de dates liées | Void |

**Option recommandée : une seule route POST avec dispatch interne**

Créer `calendrier_des_commissions_dialog_edit` (`/calendrier-des-commissions/dialogcomm`, POST) qui reçoit `do` et dispatch vers des méthodes privées. C'est un **port direct** du legacy.

```php
/**
 * @Route("/calendrier-des-commissions/dialogcomm", name="calendrier_des_commissions_dialog_edit", methods={"POST"}, options={"expose"=true})
 */
public function dialogComm(Request $request): Response
{
    $do = $request->request->get('do', '');
    switch ($do) {
        case 'edit':
            return $this->dialogEdit($request);
        case 'valid_libelleCom':
            return $this->dialogValidLibelle($request);
        // ... etc.
    }
}
```

Les templates Twig seront des **fragments** (pas de layout) rendus via `$this->render(...)` avec `new Response(...)`.

### 4.4 Templates Twig à créer

| Template | Contenu | Rendu par |
|---|---|---|
| `calendrier_des_commissions/index.html.twig` | Page principale : sidebar commissions, FullCalendar, JS complet | `indexAction` |
| `calendrier_des_commissions/dialog/_edit.html.twig` | Dialogue édition (formulaire libellé, type, tableau dates) | `dialogComm(do=edit)` |
| `calendrier_des_commissions/dialog/_libelle_display.html.twig` | Affichage libellé avec bouton édition | `dialogComm(do=valid_libelleCom, annule_libelleCom)` |
| `calendrier_des_commissions/dialog/_libelle_edit.html.twig` | Input texte + boutons valider/annuler | `dialogComm(do=libelleCom)` |
| `calendrier_des_commissions/dialog/_type_display.html.twig` | Affichage type avec bouton édition | `dialogComm(do=valid_typeCom, annule_typeCom)` |
| `calendrier_des_commissions/dialog/_type_edit.html.twig` | Select type + boutons valider/annuler | `dialogComm(do=typeCom)` |
| `calendrier_des_commissions/dialog/_date_display.html.twig` | Ligne date en lecture (date, heureD, heureF, boutons) | `dialogComm(do=valid_dateCom, annule_dateCom)` |
| `calendrier_des_commissions/dialog/_date_edit.html.twig` | Ligne date en édition (inputs) | `dialogComm(do=dateComm)` |
| `calendrier_des_commissions/dialog/_date_new.html.twig` | Ligne date ajoutée (mode création) | `dialogComm(do=addDateN)` |
| `calendrier_des_commissions/dialog/_date_linked.html.twig` | Ligne date liée (mode édition, ajout en BD) | `dialogComm(do=addDateS)` |
| `calendrier_des_commissions/alertsuppression.html.twig` | Modale confirmation suppression (fragment HTML) | `alertsuppressionAction` |

### 4.5 JavaScript

Le JS legacy est dans `index.phtml` (~600 lignes). Il utilise :
- **FullCalendar 1.x** (API ancienne : `$.fn.fullCalendar`)
- **jQuery UI** (`.dialog()`, `.draggable()`, `.datepicker()`)
- **`.live()`** (jQuery ≤1.7, deprecated)

**Stratégie JS :**

1. **Extraire le JS dans un fichier dédié** : `public/js/calendrier-commissions.js`
2. **Adapter les URLs AJAX** pour utiliser **FOSJsRoutingBundle** (déjà installé, les routes ont `options={"expose"=true}`)
3. **Remplacer `.live()`** par `.on()` (délégation d'événements)
4. **Conserver FullCalendar 1.x** — pas de montée de version (port direct)

**Modifications JS minimales :**

```javascript
// AVANT (legacy)
url: '/calendrier-des-commissions/recupevenement?start='+start.getTime()+"&end="+end.getTime(),

// APRÈS (Symfony)
url: Routing.generate('calendrier_des_commissions_recuperer_evenements') + '?start='+start.getTime()+"&end="+end.getTime(),
```

```javascript
// AVANT
url: "/calendrier-des-commissions/dialogcomm",

// APRÈS
url: Routing.generate('calendrier_des_commissions_dialog_edit'),
```

```javascript
// AVANT
url: "/calendrier-des-commissions/deplacecommissiondate",

// APRÈS
url: Routing.generate('calendrier_des_commissions_deplacer_date'),
```

**Mapping complet des URLs JS à remplacer :**

| URL legacy | Route Symfony |
|---|---|
| `/calendrier-des-commissions/recupevenement` | `calendrier_des_commissions_recuperer_evenements` |
| `/calendrier-des-commissions/dialogcomm` | `calendrier_des_commissions_dialog_edit` |
| `/calendrier-des-commissions/deplacecommissiondate` | `calendrier_des_commissions_deplacer_date` |
| `/calendrier-des-commissions/resizecommissiondate` | `calendrier_des_commissions_redimensionner_date` |
| `/calendrier-des-commissions/adddates` | `calendrier_des_commissions_creer_date` |
| `/calendrier-des-commissions/alertsuppression` | `calendrier_des_commissions_alertsuppression` |
| `/calendrier-des-commissions/validsuppression` | `calendrier_des_commissions_validsuppression` |

### 4.6 Données passées au template `index.html.twig`

| Variable | Type | Source |
|---|---|---|
| `idComm` | `int\|null` | Paramètre GET `?idComm=` |
| `array_commissions` | `array` | CommissionType → Commission (groupées par type) |
| `url_webcal` | `string` | URL webcal pour synchro Outlook (si `PREVARISC_SECURITY_KEY` configurée) |
| `is_admin` | `bool` | ACL `gestion_parametrages/gestion_commissions` |
| `is_view_all` | `bool` | ACL `commission/calendar_view_all` |

### 4.7 ACL legacy détaillée

```php
// Admin : peut voir toutes les commissions
$is_admin = $acl->isAllowed($group, 'gestion_parametrages', 'gestion_commissions');

// View all : peut cliquer "Voir tous les événements" (affichage sans filtre commission)
$is_view_all = $acl->isAllowed($group, 'commission', 'calendar_view_all');

// Filtre par commission : si !is_admin, l'utilisateur ne voit que les commissions
// auxquelles il est rattaché (in_array($item, $identity['commissions']))
```

**Côté Symfony :** Utiliser le `Security` existant (ex: `$this->isGranted(...)` ou un Voter). Vérifier comment les autres contrôleurs Commission gèrent les ACL pour rester cohérent.

### 4.8 Fichiers à créer/modifier (Phase A)

**Créer :**

| Fichier | Description |
|---|---|
| `templates/calendrier_des_commissions/index.html.twig` | Page principale |
| `templates/calendrier_des_commissions/dialog/_edit.html.twig` | Dialogue édition complet |
| `templates/calendrier_des_commissions/dialog/_libelle_display.html.twig` | Fragment libellé lecture |
| `templates/calendrier_des_commissions/dialog/_libelle_edit.html.twig` | Fragment libellé édition |
| `templates/calendrier_des_commissions/dialog/_type_display.html.twig` | Fragment type lecture |
| `templates/calendrier_des_commissions/dialog/_type_edit.html.twig` | Fragment type édition |
| `templates/calendrier_des_commissions/dialog/_date_display.html.twig` | Fragment date lecture |
| `templates/calendrier_des_commissions/dialog/_date_edit.html.twig` | Fragment date édition |
| `templates/calendrier_des_commissions/dialog/_date_new.html.twig` | Fragment ajout date (création) |
| `templates/calendrier_des_commissions/dialog/_date_linked.html.twig` | Fragment ajout date liée |
| `templates/calendrier_des_commissions/alertsuppression.html.twig` | Fragment confirmation suppression |
| `public/js/calendrier-commissions.js` | JS extrait de index.phtml |

**Modifier :**

| Fichier | Modification |
|---|---|
| `src/Controller/Commission/CalendrierDesCommissionsController.php` | Ajouter `indexAction`, `dialogComm`, `alertsuppression`, `validsuppression`, `commissionselection` |
| `src/Service/Commission/CalendarService.php` | Ajouter méthodes : `updateLibelle`, `updateType`, `deleteDate`, `addLinkedDate`, `changeMasterDate` |
| `src/Repository/DateCommissionRepository.php` | Ajouter méthodes : `getCommissionsQtypListing`, `dateCommUpdateLibelle`, `dateCommUpdateType`, `changeMasterDateComm`, `updateDependingDossierDates` |

---

## 5. Phase B — Gestion ODJ

### 5.1 Routes Symfony à créer

| Route | URL | Méthode | Rôle |
|---|---|---|---|
| `calendrier_des_commissions_gestion_odj` | `/calendrier-des-commissions/gestionodj/{dateCommId}` | GET | Page ODJ d'une date de commission |
| `calendrier_des_commissions_recup_evenement_odj` | `/calendrier-des-commissions/recupevenementodj` | GET (AJAX/JSON) | Récupère les dossiers affectés pour le calendrier agendaDay |
| `calendrier_des_commissions_affecter_dossier_odj` | `/calendrier-des-commissions/affectedossodj` | POST (AJAX/JSON) | Affecte un dossier à un créneau (drag-drop) |
| `calendrier_des_commissions_resize_odj` | `/calendrier-des-commissions/resizeodj` | POST (AJAX) | Redimensionne un dossier dans l'ODJ |
| `calendrier_des_commissions_drop_odj` | `/calendrier-des-commissions/dropodj` | POST (AJAX) | Déplace un dossier dans le calendrier ODJ |
| `calendrier_des_commissions_gestion_heures` | `/calendrier-des-commissions/gestionheures` | POST (AJAX) | Active/désactive la gestion des heures |
| `calendrier_des_commissions_changement_ordre` | `/calendrier-des-commissions/changementordre` | POST (AJAX) | Réordonne les dossiers (mode sans heures) |

### 5.2 Templates Twig à créer

| Template | Description |
|---|---|
| `calendrier_des_commissions/gestionodj.html.twig` | Page ODJ complète : sidebar actions + calendrier agendaDay + liste dossiers |
| `public/js/calendrier-commissions-odj.js` | JS dédié à la page ODJ (FullCalendar agendaDay, drag-drop, sortable) |

### 5.3 Entités et repositories nécessaires

La page ODJ nécessite des requêtes sur `DossierAffectation`. Vérifier l'existence de :
- `DossierAffectationRepository` avec les méthodes `getDossierAffect`, `getDossierNonAffect`, `getAllDossierAffect`
- Accès au `Service\Etablissement` pour les infos enrichies des établissements
- `DossierDocUrbaRepository` pour les documents d'urbanisme

### 5.4 Logique complexe

La page ODJ a **deux modes** d'affichage :
1. **Mode heures** (`GESTION_HEURES = 1`) : FullCalendar agendaDay avec drag-drop
2. **Mode ordre** (`GESTION_HEURES = 0`) : Liste sortable jQuery UI (pas de calendrier)

Le basculement se fait via `gestionheuresAction` qui remet à null les heures de tous les dossiers affectés.

---

## 6. Phase C — Génération de documents

### 6.1 Routes Symfony à créer

| Route | URL | Méthode | Rôle |
|---|---|---|---|
| `calendrier_des_commissions_generation_convoc` | `/calendrier-des-commissions/generationconvoc` | POST (AJAX/HTML) | Génère convocations ODT |
| `calendrier_des_commissions_generation_odj` | `/calendrier-des-commissions/generationodj` | POST (AJAX/HTML) | Génère l'ordre du jour ODT |
| `calendrier_des_commissions_generation_pv` | `/calendrier-des-commissions/generationpv` | POST (AJAX/HTML) | Génère le PV ODT |
| `calendrier_des_commissions_generation_compterendu` | `/calendrier-des-commissions/generationcompterendu` | POST (AJAX/HTML) | Génère le compte rendu ODT |

### 6.2 Analyse de complexité

C'est la partie la **plus complexe** du module. Chaque action :

1. Récupère les dossiers avec leurs établissements, adresses, communes
2. Récupère les membres de la commission avec leurs courriers modèles
3. Pour chaque dossier, enrichit avec : préventionnistes, documents urbanisme, dérogations, formulaires personnalisés
4. Pour la convocation : filtre les membres selon catégorie/type/classe de l'établissement
5. Génère des documents ODT via la librairie `Odf` (OpenDocument PHP) avec des segments (boucles dans le template ODT)

**Dépendances clés :**
- `Odf` (librairie PHP OpenDocument) — Vérifier son équivalent côté Symfony (probablement via `AbstractOdtDocumentService` déjà existant)
- `Service_DossierVerificationsTechniques`, `Service_EtablissementDescriptif`, `Service_DossierEffectifsDegagements`, `Service_EtablissementEffectifsDegagements` — Services de rubriques/formulaires personnalisés
- `Service_Formulaire` — Capsules de rubriques
- `Service_Dossier::getPrescriptions` — Pour le PV (prescriptions réglementaires, exploitation, amélioration)

### 6.3 Services existants réutilisables

| Service existant | Utilisation |
|---|---|
| `AbstractOdtDocumentService` | Base pour la génération ODT |
| `CommissionDocumentBuilder` | Construction de documents commission |
| `CommissionDocumentCrService` | Comptes rendus |
| `CommissionMembreCourrierService` | Courriers des membres |
| `Prescriptions` (Service) | Récupération prescriptions |
| `Etablissement` (Service) | Infos établissements enrichies |
| `DescriptifFormBuilder` / `DescriptifFormHydrator` | Formulaires personnalisés |

### 6.4 Templates Twig à créer

Les vues legacy (`generationconvoc.phtml`, `generationodj.phtml`, `generationpv.phtml`, `generationcompterendu.phtml`) sont des **pages HTML de résultat** affichées via AJAX dans `#infoGeneration`. Elles contiennent les liens de téléchargement des documents générés.

| Template | Contenu |
|---|---|
| `calendrier_des_commissions/generation/_convoc_result.html.twig` | Résultat génération convocations (liens téléchargement) |
| `calendrier_des_commissions/generation/_odj_result.html.twig` | Résultat génération ODJ |
| `calendrier_des_commissions/generation/_pv_result.html.twig` | Résultat génération PV |
| `calendrier_des_commissions/generation/_compterendu_result.html.twig` | Résultat génération compte rendu |

---

## 7. Phase D — Export Outlook

### 7.1 Routes Symfony à créer

| Route | URL | Méthode | Rôle |
|---|---|---|---|
| `calendrier_des_commissions_export_outlook` | `/calendrier-des-commissions/exportoutlook/{dateCommId}` | GET | Export ICS d'une seule date |
| `calendrier_des_commissions_export_outlook_mois` | `/calendrier-des-commissions/exportoutlookmois/{commId}/{mois}/{annee}` | GET | Export ICS d'un mois complet |

### 7.2 Logique

Port direct simple :
- Construire un fichier ICS (`text/Calendar`)
- `exportoutlookAction` : une seule date → un VEVENT
- `exportoutlookmoisAction` : toutes les dates d'un mois pour une commission → N VEVENT, avec description incluant l'ODJ

### 7.3 Fichiers à créer

| Fichier | Description |
|---|---|
| Ajout dans `CalendrierDesCommissionsController.php` | 2 méthodes + retour `Response` avec headers ICS |

**Aucun template Twig nécessaire** — le contenu ICS est généré directement dans le contrôleur.

### 7.4 Repository nécessaire

`DateCommissionRepository::getMonthCommission($mois, $annee, $idComm)` — probablement à ajouter.

---

## 8. Dépendances et risques

### 8.1 Dépendances entre phases

```
Phase A (calendrier)
  └──> Phase B (ODJ) : le bouton "Afficher l'ordre du jour" dans le dialogue de A
       └──> Phase C (documents) : les boutons de génération sont dans la page ODJ de B

Phase D (Outlook) : indépendant, accessible depuis le menu latéral de A et B
```

### 8.2 Risques identifiés

| Risque | Sévérité | Phase | Mitigation |
|---|---|---|---|
| **dialogcommAction mega-switch** : 16 cas dans une seule action, gestion d'état complexe | 🟡 Medium | A | Port direct avec dispatch interne, templates fragments. Tester chaque cas individuellement. |
| **FullCalendar 1.x API** : API ancienne (`.fullCalendar()`) | 🟢 Low | A, B | Conserver la version existante, pas de montée de version. |
| **jQuery `.live()` deprecated** | 🟡 Medium | A, B | Remplacer par `.on()` avec délégation. Syntaxe quasi identique. |
| **Librairie Odf pour génération ODT** | 🔴 High | C | Vérifier que `AbstractOdtDocumentService` couvre les besoins. La librairie Odf utilise des segments (boucles) complexes. |
| **N+1 queries dans génération docs** | 🟡 Medium | C | Le legacy a des N+1 (boucle foreach avec des `find()` individuels). Port direct = reproduire les mêmes N+1. Documenter pour optimisation future. |
| **Rubriques/formulaires personnalisés** | 🟡 Medium | C | Les services `DescriptifFormBuilder`/`DescriptifFormHydrator` existent. Vérifier qu'ils exposent les mêmes données que `Service_DossierVerificationsTechniques` et équivalents. |
| **Prescriptions dans PV** | 🟡 Medium | C | `DossierPrescriptionService` existe côté Symfony. Vérifier la parité avec `Service_Dossier::getPrescriptions`. |
| **Suppression en cascade** (`validsuppression`) | 🟡 Medium | A | Supprime dossier_affectation, PJ physiques et BD, dates liées. Tester minutieusement. |
| **ACL filtre commissions par utilisateur** | 🟢 Low | A | Le legacy filtre en PHP via `in_array($item, $identity['commissions'])`. Reproduire côté Twig/Controller. |

### 8.3 Parties complexes à surveiller

1. **Génération convocations** (`generationconvocAction`, ~150 lignes) : filtre les dossiers par commune et par compétence membre (catégorie + type activité + classe). La vue `generationconvoc.phtml` fait ~300 lignes de génération ODT avec segments imbriqués.

2. **Génération PV** (`generationpvAction`, ~170 lignes) : inclut les prescriptions (réglementaires, exploitation, amélioration) avec gestion reprises/actuelles/levées.

3. **Gestion des dates liées** : concept de "date maître" et "dates liées" (`DATECOMMISSION_LIEES`). La logique `changeMasterDateComm` + `makeDefaut` est subtile.

---

## 9. Estimation effort par phase

| Phase | Actions restantes | Complexité | Templates | JS | Estimation |
|---|---|---|---|---|---|
| **A — Calendrier** | 7 | Medium | 11 templates | 1 fichier JS (~500 lignes) | **3-4 jours** |
| **B — ODJ** | 7 | Medium-High | 1 template + 1 JS | 1 fichier JS (~300 lignes) | **3-4 jours** |
| **C — Documents** | 4 | High | 4 templates fragment | — | **5-7 jours** |
| **D — Outlook** | 2 | Low | 0 | — | **0.5 jour** |
| **Total** | **20** | | **16 templates** | **2 fichiers JS** | **12-16 jours** |

### Détail Phase A (prioritaire)

| Tâche | Estimation | Détail |
|---|---|---|
| `indexAction` + template | 0.5j | Page simple, sidebar commissions |
| JS calendrier | 1j | Extraction, remplacement URLs, `.live()` → `.on()` |
| `dialogcommAction` (16 cas) | 1.5j | Port du mega-switch + 10 templates fragments |
| `validsuppression` | 0.5j | Suppression cascade (dossiers, PJ, dates) |
| `commissionselection` + `alertsuppression` | 0.5j | Endpoints simples |
| Tests + validation PHPStan/CS | 0.5j | |

---

## 10. Recommandation

### Démarrer par la Phase A

**Raison :** C'est le point d'entrée du module. Sans la page principale, les phases B, C et D ne sont pas accessibles. De plus :
- 5 routes AJAX sont déjà migrées (le calendrier affiche déjà les événements)
- Le plus gros travail est le `dialogcommAction` (édition inline), mais c'est du HTML fragment — pas de logique métier complexe
- La Phase D (Outlook) peut être faite en parallèle car elle est indépendante

### Ordre d'implémentation recommandé pour Phase A

1. **`indexAction`** — page vide avec sidebar commissions (permet de valider le layout)
2. **JS calendrier** — extraction du JS, adaptation des URLs → le calendrier fonctionne
3. **`dialogcommAction` cas `edit`** — le clic sur un événement affiche le dialogue (lecture seule d'abord)
4. **`dialogcommAction` cas édition** (libelleCom, typeCom, dateCom) — édition inline
5. **`dialogcommAction` cas ajout/suppression** (addDateS, supp_dateCom, makeDefaut)
6. **`alertsuppressionAction` + `validsuppressionAction`** — suppression complète
7. **`commissionselectionAction`** — autocomplete (priorité basse, utilisé par d'autres modules)

### Phase D en parallèle

La Phase D (2 actions Outlook) est totalement indépendante et peut être réalisée en parallèle de la Phase A par un autre développeur ou en worktree séparé.

---

## Annexe : Mapping JS URL legacy → Symfony (référence complète)

```javascript
// Phase A
'/calendrier-des-commissions/recupevenement'     → Routing.generate('calendrier_des_commissions_recuperer_evenements')
'/calendrier-des-commissions/dialogcomm'          → Routing.generate('calendrier_des_commissions_dialog_edit')
'/calendrier-des-commissions/deplacecommissiondate' → Routing.generate('calendrier_des_commissions_deplacer_date')
'/calendrier-des-commissions/resizecommissiondate'  → Routing.generate('calendrier_des_commissions_redimensionner_date')
'/calendrier-des-commissions/adddates'            → Routing.generate('calendrier_des_commissions_creer_date')
'/calendrier-des-commissions/alertsuppression'    → Routing.generate('calendrier_des_commissions_alertsuppression')
'/calendrier-des-commissions/validsuppression'    → Routing.generate('calendrier_des_commissions_validsuppression')

// Phase B
'/calendrier-des-commissions/recupevenementodj'   → Routing.generate('calendrier_des_commissions_recup_evenement_odj')
'/calendrier-des-commissions/dropodj'             → Routing.generate('calendrier_des_commissions_drop_odj')
'/calendrier-des-commissions/affecteDossOdj'      → Routing.generate('calendrier_des_commissions_affecter_dossier_odj')
'/calendrier-des-commissions/resizeodj'           → Routing.generate('calendrier_des_commissions_resize_odj')
'/calendrier-des-commissions/gestionheures'       → Routing.generate('calendrier_des_commissions_gestion_heures')
'/calendrier-des-commissions/changementordre'     → Routing.generate('calendrier_des_commissions_changement_ordre')
'/calendrier-des-commissions/generationconvoc'    → Routing.generate('calendrier_des_commissions_generation_convoc')
'/calendrier-des-commissions/generationodj'       → Routing.generate('calendrier_des_commissions_generation_odj')
'/calendrier-des-commissions/generationpv'        → Routing.generate('calendrier_des_commissions_generation_pv')
'/calendrier-des-commissions/generationcompterendu' → Routing.generate('calendrier_des_commissions_generation_compterendu')

// Phase D
'/calendrier-des-commissions/exportoutlook/dateCommId/X' → Routing.generate('calendrier_des_commissions_export_outlook', {dateCommId: X})
'/calendrier-des-commissions/exportoutlookmois/CommId/X/Mois/M/Annee/Y' → Routing.generate('calendrier_des_commissions_export_outlook_mois', {commId: X, mois: M, annee: Y})
```
