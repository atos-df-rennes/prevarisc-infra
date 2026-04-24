# Git Flow — Prevarisc

Ce document décrit le flux Git utilisé sur le projet Prevarisc. Il est destiné aux nouveaux arrivants afin de comprendre rapidement comment le code est organisé et comment contribuer.

---

## Vue d'ensemble des branches

```
main        ──[v2.8.0]───────────────────────[v2.8.17]──[v2.9.0]──▶
                 │                                ↑          ↑
release/2.8      └──────●──────●──────●───────────●          │   (maintenance, supprimée après 2.9 en prod)
                         ↑      ↑      ↑                      │
bugfix/xxx           ────●  ────●  ────●                      │   (partent de release/2.8, cascade upwards)
                         ↓ cascade merge upwards              │
release/2.9              ●──────●──────●──────────────────────●   (stabilisation → livraison)
                         ↓ cascade merge upwards
develop     ─────────────●──────●──────●──────────────────────●──▶ (intégration continue)
                         │
feature/xxx  ────────────●──────────────────▶ develop ou release/2.9
```

---

## Branches et leur rôle

### `main`

- Branche de **production**
- Contient uniquement du code **stable et livré**
- Chaque version livrée est **taguée** (`v2.8.0`, `v2.9.0`, `v2.9.1`…) — voir [Gestion des versions et tags](#gestion-des-versions-et-tags)
- Ne reçoit des merges que depuis :
  - les branches `release/x.y` (nouvelle version mineure)
  - les branches `hotfix/x.y.z` (correctif urgent en production)

### `develop`

- Branche d'**intégration continue**
- Reçoit les features et bugfixes une fois **stabilisés**
- Sert de base pour créer de nouvelles branches `release/x.y` et `feature/xxx`
- Toujours dans un état **potentiellement livrable**, mais peut contenir des travaux en cours

### `release/x.y` _(ex: `release/2.8`, `release/2.9`)_

Cette branche a **deux rôles distincts** selon son état dans le cycle de vie :

**1. Branche de stabilisation (avant livraison initiale)**
- Créée depuis `develop` pour préparer une nouvelle version mineure
- Permet à `develop` de continuer à avancer sans être bloqué
- Reçoit les corrections de stabilisation avant la mise en prod
- Une fois stable, mergée dans `main` (avec tag) **et** dans `develop`

**2. Branche de maintenance (après livraison initiale)**
- Reste active tant qu'une version plus récente n'a pas remplacé celle-ci en production
- Reçoit les `bugfix/xxx` ciblant cette version
- Les corrections sont **propagées en cascade vers le haut** : `release/2.8` → `release/2.9` → `develop`
- Permet de livrer des versions patch (`v2.8.17`) sans impacter le travail en cours sur `develop`
- **Supprimée** une fois que la version suivante (`release/2.9`) est livrée à l'ensemble des clients

> **Exemple de cycle de vie :**
> `release/2.8` est créée pour livrer v2.8.0, puis reste active en maintenance pendant que `release/2.9` est en préparation.
> Chaque correctif sur `release/2.8` est cascadé vers `release/2.9` puis `develop`.
> Une fois v2.9.0 livré à tous les clients, `release/2.8` est supprimée.

### `feature/xxx`

- Créée depuis `develop` pour développer **une nouvelle fonctionnalité**
- Nommage : `feature/nom-court-de-la-feature` (ex: `feature/edition-dossier-phase3`)
- Mergée dans `develop` ou dans la branche `release/x.y` concernée une fois terminée

### `bugfix/xxx`

- Créée depuis **`release/x.y` (version maintenue)** ou `develop` selon le contexte :
  - Si le bug affecte une version en maintenance → partir de `release/x.y`
  - Si le bug n'affecte que le travail en cours → partir de `develop`
- Nommage : `bugfix/description-courte` (ex: `bugfix/affichage-date-commission`)
- Mergée dans sa branche source, puis propagée en cascade vers le haut

### `hotfix/x.y.z`

- Créée depuis `main` pour corriger **un bug critique en production**
- Nommage : `hotfix/x.y.z` (ex: `hotfix/2.8.1`)
- Mergée dans `main` (avec tag de version patch) **et** dans `develop` pour ne pas perdre le correctif

---

## Cycle de vie d'une version

### Nouvelle version mineure (ex: `v2.9.0`)

```
1. Créer release/2.9 depuis develop
   git checkout develop
   git checkout -b release/2.9

2. Développer / intégrer les features sur release/2.9
   (corrections de stabilisation si nécessaire)

3. Une fois stable : merger dans main
   git checkout main
   git merge --no-ff release/2.9
   # Le tag est généré via conventional-changelog (voir section dédiée)
   git tag v2.9.0

4. Répercuter sur develop
   git checkout develop
   git merge --no-ff release/2.9

5. Supprimer la branche release
   git branch -d release/2.9
```

### Correctif sur version maintenue (ex: `v2.8.17`)

Utilisé lorsqu'une version est encore en production pendant que la suivante est en préparation.
Le correctif part de la branche de maintenance et est propagé en cascade vers le haut.

```
1. Créer le bugfix depuis la branche de maintenance
   git checkout release/2.8
   git checkout -b bugfix/correction-xxx

2. Corriger le bug, commiter

3. Merger dans release/2.8
   git checkout release/2.8
   git merge --no-ff bugfix/correction-xxx
   git branch -d bugfix/correction-xxx

4. Cascader vers release/2.9 (si elle existe)
   git checkout release/2.9
   git merge --no-ff release/2.8

5. Cascader vers develop
   git checkout develop
   git merge --no-ff release/2.9   # ou release/2.8 si release/2.9 n'existe pas

6. Livrer le patch : merger release/2.8 dans main et tagger
   git checkout main
   git merge --no-ff release/2.8
   # Le tag est généré via conventional-changelog (voir section dédiée)
   git tag v2.8.17
```

> **Note :** La cascade peut se faire immédiatement après chaque correctif, ou être regroupée avant chaque livraison de patch selon le rythme de l'équipe.

> **Fin de vie :** Une fois `v2.9.0` livré à l'ensemble des clients en production, `release/2.8` est supprimée. Les correctifs ne sont plus backportés sur cette branche.
> À ce moment, exécuter **`castor git:set-first-release`** pour indiquer que `release/2.9` est désormais la première branche supportée. Les hotfixes depuis `main` cascaderont directement vers `release/2.9` (et non plus vers l'ancienne `release/2.8`). Commiter le fichier `.merge-upwards-config.json` généré pour partager la configuration avec toute l'équipe.

### Hotfix urgent en production (ex: `v2.8.1`)

Réservé aux bugs critiques nécessitant une correction immédiate, sans passer par le cycle de release normal.

> **À utiliser avec parcimonie.** Dans la plupart des cas, un correctif sur une version maintenue doit passer par `release/x.y` (voir section précédente). Le hotfix depuis `main` est justifié uniquement quand l'urgence ne permet pas d'attendre le cycle normal.

```
1. Créer hotfix depuis main
   git checkout main
   git checkout -b hotfix/2.8.1

2. Corriger le bug, commiter

3. Merger dans main et tagger
   git checkout main
   git merge --no-ff hotfix/2.8.1
   # Le tag est généré via conventional-changelog (voir section dédiée)
   git tag v2.8.1

4. Cascader vers toutes les branches release actives puis develop
   castor git:merge-upwards --from main
   # Cascade automatique : main → release/2.8 → release/2.9 → develop
   # (ou main → release/2.9 → develop si release/2.8 n'est plus maintenue,
   #  voir castor git:set-first-release)

5. Supprimer la branche hotfix
   git branch -d hotfix/2.8.1
```

> **Pourquoi cascader vers les branches release ?**
> Si `release/2.8` est active et ne reçoit pas le hotfix, le prochain merge de `release/2.8` vers `main` risque de créer un conflit ou de réintroduire le bug corrigé.

---

## Gestion des versions et tags

Les tags de version sont générés via **[marcocesarato/php-conventional-changelog](https://github.com/marcocesarato/php-conventional-changelog)**, qui analyse l'historique des commits pour déterminer automatiquement le prochain numéro de version et générer le changelog.

### Prérequis

Les commits doivent respecter la convention **Conventional Commits** (cf. section [Conventions de commit](#conventions-de-commit)) pour que conventional-changelog puisse les interpréter correctement.

### Calcul du numéro de version (SemVer)

| Type de commit         | Impact sur la version | Exemple                    |
|------------------------|-----------------------|----------------------------|
| `fix`                  | Patch (`x.y.Z`)       | `v2.9.0` → `v2.9.1`       |
| `feat`                 | Mineure (`x.Y.z`)     | `v2.9.0` → `v2.10.0`      |
| `BREAKING CHANGE`      | Majeure (`X.y.z`)     | `v2.9.0` → `v3.0.0`       |

### Génération du tag et du changelog

```bash
# Installer conventional-changelog via Composer si nécessaire
composer require marcocesarato/php-conventional-changelog --dev

# Générer le changelog et bumper la version automatiquement
vendor/bin/conventional-changelog

# Créer le tag Git correspondant (suggéré par la commande précédente)
git tag vx.y.z
git push origin vx.y.z
```

> Le fichier `CHANGELOG.md` est mis à jour automatiquement avec la liste des commits classés par type (`feat`, `fix`, etc.).

---

| Type       | Format                          | Exemple                        |
|------------|---------------------------------|--------------------------------|
| Feature    | `feature/nom-court`             | `feature/gestion-commissions`  |
| Bugfix     | `bugfix/description-courte`     | `bugfix/correction-pagination` |
| Hotfix     | `hotfix/x.y.z`                  | `hotfix/2.8.1`                 |
| Release    | `release/x.y`                   | `release/2.9`                  |
| Tag        | `vx.y.z`                        | `v2.9.0`                       |

---

## Conventions de commit

Format : `<type>(<scope>): <description>`

| Type     | Usage                                      |
|----------|--------------------------------------------|
| `feat`   | Nouvelle fonctionnalité                    |
| `fix`    | Correction de bug                          |
| `docs`   | Documentation uniquement                  |
| `refactor` | Refactoring sans changement de comportement |
| `test`   | Ajout ou modification de tests             |
| `chore`  | Tâches annexes (dépendances, config…)      |

Exemples :
```
feat(dossier): ajout nature 21 - Phase 3
fix(dossier): correction affichage date commission
docs(git): ajout documentation du flow Git
hotfix(auth): correction session expirée en production
```

---

## Schéma complet

```
main        ──[v2.8.0]──────────────────────────[v2.8.17]──[v2.9.0]──▶
                 │                                    ↑          ↑
release/2.8      └──●──────●────●────●────────────────●          │
                    │      ↑    ↑    ↑                            │
bugfix/xxx          │    ──●  ──●  ──●  (partent de release/2.8) │
                    │      ↓ cascade merge upwards                │
release/2.9         └──────●────●────●──────────────────────────►─┘
                           ↓ cascade merge upwards
develop     ───────────────●────●────●──────────────────────────►──▶
                           │
feature/xxx            ────●──────────────────────────►─┘ (merge dans develop ou release/x.y)
```

---

## En résumé

| Situation | Branche source | Branche cible |
|----------------------------------------|----------------|-------------------------------|
| Nouvelle feature | `develop` | `develop` ou `release/x.y` |
| Correction bug sur version maintenue | `release/x.y` | `release/x.y` → cascade up |
| Correction bug (non urgent, develop) | `develop` | `develop` |
| Stabilisation d'une version mineure | `develop` | `release/x.y` |
| Livraison d'une version mineure | `release/x.y` | `main` + `develop` |
| Correctif urgent en production | `main` | `main` + `develop` |
| Livraison d'un patch | `release/x.y` (maintenance) | `main` (tag `vx.y.z`) |

---

> **Note pour les personnes concernées :** le processus de packaging (sélection de la branche ou du tag à livrer) est documenté sur Redmine. Consultez la documentation de packaging interne pour les détails de déploiement via l'outil de CI.
