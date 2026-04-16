---
name: migration-status
description: Analyse l'état réel de la migration Zend→Symfony en scannant les deux dépôts et le manifest. À utiliser pour savoir quoi prioriser, quelles tâches peuvent être faites en parallèle via worktree, et pour mettre à jour le manifest après une migration.
---

# Skill : État de la migration — Prevarisc

Ce skill produit un rapport de progression basé sur **la réalité du code**, pas sur un document texte potentiellement obsolète.

## Sources de vérité (à lire dans cet ordre)

1. **`docs/tech/migration/MANIFEST.yaml`** — mapping sémantique legacy → routes Symfony, avec statuts et priorités
2. **`castor migration:progress`** — scan live des deux dépôts + validation du manifest
3. **`prevarisc/application/controllers/`** — source des actions legacy
4. **`prevarisc-migration/src/Controller/`** — source des routes Symfony migrées

## Processus de réponse à "Que faire ensuite ?"

### Étape 1 — Lancer le scan live

```bash
castor migration:progress --todo
```

Ce rapport montre :
- Les tâches `partial` (en cours, à finir en priorité)
- Les tâches `todo` par ordre de priorité
- Les incohérences manifest ↔ code (routes déclarées done mais absentes)

### Étape 2 — Vérifier la réalité du code pour les tâches `partial`

Pour chaque tâche `partial`, lire les actions legacy non couvertes et les comparer aux routes existantes :

```bash
# Actions legacy du controller concerné
grep -n "function.*Action" prevarisc/application/controllers/[NomController].php

# Routes Symfony existantes pour ce scope
grep -rn "@Route" prevarisc-migration/src/Controller/ | grep 'name="[préfixe]'
```

### Étape 3 — Formuler la recommandation

Format de réponse attendu :

```
ÉTAT DE LA MIGRATION (au [date])
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Progression : ██████████░░░░░░░░░░ 52% (13/25 tâches)
✅ done    : 13    ⚠️ partial : 3    ❌ todo : 9

PROCHAINES TÂCHES RECOMMANDÉES (par priorité) :
1. [calendrier-commissions] ⚠️ partial — p2 🔴 high
   5/25 actions migrées. Reste : gestion ODJ, génération docs, export Outlook.
   
2. [dossier-pieces-jointes] ⚠️ partial — p2 🟡 medium
   Upload/suppression/ZIP non migrés. Routes PieceJointeController à créer.
   
3. [search] ⚠️ partial — p2 🟡 medium
   Recherche de base OK. Export Calc (privileges 200/210) manquant.

TÂCHES PARALLÉLISABLES VIA WORKTREE :
• [statistiques] + [admin-gestion-communes] → aucun conflit d'entités
• [export-calc] + [retablir] → aucun conflit d'entités
```

## Processus de mise à jour du manifest

**Règle : mettre à jour le manifest dans le même commit que la migration.**

Après avoir migré une tâche :

1. Localiser la tâche dans `docs/tech/migration/MANIFEST.yaml`
2. Changer `status: todo` → `status: done` (ou `partial`)
3. Ajouter les routes créées dans le champ `routes:`
4. Valider la cohérence :
   ```bash
   castor migration:progress  # vérifie que les routes existent bien dans le code
   ```
5. Inclure dans le commit :
   ```
   feat(scope): description de la migration
   
   - Met à jour MANIFEST.yaml : [task-id] → done
   
   Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
   ```

## Analyse de parallélisation

Pour identifier les tâches faisables en worktree simultané :

```bash
castor migration:progress --parallel
```

Deux tâches sont **parallélisables** si :
- Elles n'utilisent pas les mêmes entités Doctrine (pas de conflits de fichiers)
- Elles ne dépendent pas l'une de l'autre (ex: `dossier-odj` dépend de `calendrier-commissions`)

**Workflow worktree** : voir `WORKFLOW_WORKTREE.md` pour la procédure complète.

## Format du manifest (référence)

```yaml
- id: nom-de-la-tache           # kebab-case, unique
  description: "Description"    # ce que fait la fonctionnalité
  status: done|partial|todo|skip
  priority: 1                   # 1 (urgent) → 5 (nice to have)
  complexity: low|medium|high
  entities: [Entite1, Entite2]  # entités Doctrine concernées
  legacy:                       # actions legacy couvertes
    - "DossierController#editAction"
  routes:                       # routes Symfony créées
    - dossier_edit
  notes: >                      # optionnel — ce qui reste à faire si partial
    Détail des actions restantes.
```

## Cas particuliers

### Controller legacy splitté en plusieurs controllers Symfony
Documenter dans le manifest avec toutes les routes Symfony correspondantes.
Exemple : `DossierController` (59 actions) → `DossierController` + `DossierAjaxController` + `DossierContactController` + etc.

### Action legacy sans équivalent Symfony prévu
Utiliser `status: skip` avec une note explicative dans `notes:`.

### Incohérence manifest ↔ code
Si `castor migration:progress` signale une route déclarée `done` mais absente du code :
1. Soit la route a été renommée → corriger dans le manifest
2. Soit la tâche est en fait `partial` → corriger le statut
