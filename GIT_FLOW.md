# Git Flow — Prevarisc

Ce document décrit le flux Git utilisé sur le projet Prevarisc. Il est destiné aux nouveaux arrivants afin de comprendre rapidement comment le code est organisé et comment contribuer.

---

## Vue d'ensemble des branches

```
main        ──●─────────────────────────────────────────●──── (tags: v2.8.0, v2.9.0…)
               \                                       /
hotfix          ────────●──────────────────────────────        (ex: hotfix/2.8.1)
                        ↑ merge → main (+ tag)
                        ↑ merge → develop (si besoin)

develop     ──────────────────●────────●────────●───────────── (intégration continue)
                             /          \
release/2.9 ───────────────●────────────●                      (stabilisation)
                          /              \
feature/xxx ─────────────●               ↑ merge → develop (une fois stable)

bugfix/xxx  ──────────────────────────────●────●               (merge → develop)
```

---

## Branches et leur rôle

### `main`

- Branche de **production**
- Contient uniquement du code **stable et livré**
- Chaque version livrée est **taguée** (`v2.8.0`, `v2.9.0`, `v2.9.1`…)
- Ne reçoit des merges que depuis :
  - les branches `release/x.y` (nouvelle version mineure)
  - les branches `hotfix/x.y.z` (correctif urgent en production)

### `develop`

- Branche d'**intégration continue**
- Reçoit les features et bugfixes une fois **stabilisés**
- Sert de base pour créer de nouvelles branches `release/x.y` et `feature/xxx`
- Toujours dans un état **potentiellement livrable**, mais peut contenir des travaux en cours

### `release/x.y` _(ex: `release/2.9`)_

- Créée depuis `develop` pour **stabiliser une nouvelle version mineure**
- Permet à `develop` de **continuer à avancer** sans être bloqué par une feature instable
- Reçoit des corrections propres à cette release (bugfix de stabilisation)
- Une fois stable, mergée dans `develop` **et** dans `main` (avec tag)

> **Pourquoi cette branche ?**
> Si une feature importante est en cours de stabilisation sur `release/2.9`, les autres développeurs peuvent continuer à merger des features ou bugfixes sur `develop` sans attendre. Cela évite de bloquer les livraisons.

### `feature/xxx`

- Créée depuis `develop` pour développer **une nouvelle fonctionnalité**
- Nommage : `feature/nom-court-de-la-feature` (ex: `feature/edition-dossier-phase3`)
- Mergée dans `develop` ou dans la branche `release/x.y` concernée une fois terminée

### `bugfix/xxx`

- Créée depuis `develop` pour corriger **un bug non urgent**
- Nommage : `bugfix/description-courte` (ex: `bugfix/affichage-date-commission`)
- Mergée dans `develop`

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
   git tag v2.9.0

4. Répercuter sur develop
   git checkout develop
   git merge --no-ff release/2.9

5. Supprimer la branche release
   git branch -d release/2.9
```

### Hotfix urgent (ex: `v2.8.1`)

```
1. Créer hotfix depuis main
   git checkout main
   git checkout -b hotfix/2.8.1

2. Corriger le bug, commiter

3. Merger dans main et tagger
   git checkout main
   git merge --no-ff hotfix/2.8.1
   git tag v2.8.1

4. Répercuter sur develop
   git checkout develop
   git merge --no-ff hotfix/2.8.1

5. Supprimer la branche hotfix
   git branch -d hotfix/2.8.1
```

---

## Conventions de nommage

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
main        ─────────────[v2.8.0]──────────────────────────────[v2.8.1]──[v2.9.0]──▶
                          │                                      │         │
hotfix/2.8.1              │                          ┌──────────┘         │
                          │                          │ (correctif urgent) │
develop     ──────────────●────────●────────●────────●───────────────────●──────────▶
                          │        │        │                    ▲
release/2.9               │        └────────┼──────────────────►─┘
                          │     (stabilisation)                 │
feature/xxx               └────●──────────►─┘ (merge feature)  │
                                                                 │
bugfix/xxx                           ────────●──────────────────┘
```

---

## En résumé

| Situation                              | Branche source | Branche cible              |
|----------------------------------------|----------------|----------------------------|
| Nouvelle feature                       | `develop`      | `develop` ou `release/x.y` |
| Correction de bug (non urgent)         | `develop`      | `develop`                  |
| Stabilisation d'une version mineure    | `develop`      | `release/x.y`              |
| Livraison d'une version mineure        | `release/x.y`  | `main` + `develop`         |
| Correctif urgent en production         | `main`         | `main` + `develop`         |

---

> **Note pour les personnes concernées :** le processus de packaging (sélection de la branche ou du tag à livrer) est documenté sur Redmine. Consultez la documentation de packaging interne pour les détails de déploiement via l'outil de CI.
