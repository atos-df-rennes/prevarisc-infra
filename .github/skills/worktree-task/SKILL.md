---
name: worktree-task
description: "Orchestre les tâches légères (refactor, modernisation, corrections) dans le contexte isolé du worktree Prevarisc. À utiliser pour toute tâche effectuée dans le worktree, sans avoir à répéter les conteneurs, répertoires ou commandes spécifiques à chaque fois."
---

# Skill : Tâche dans le worktree — Prevarisc

> DEPRECATED : Cette skill a été convertie en agent `worktree-task` (voir `.github/agents/worktree-task.agent.md`). Les opérations de worktree doivent être lancées via l'agent avec `run_context: worktree`. La skill reste disponible pour documentation.

Ce skill fournit le contexte complet pour toute tâche effectuée dans un worktree Git en parallèle de la branche principale. Plus besoin de mentionner le conteneur ou les répertoires worktree dans chaque prompt.

**Run context (opt‑in)** : Ce skill agit exclusivement dans un worktree et est **opt‑in**. Il ne sera invoqué automatiquement que si `run_context: worktree` est explicitement demandé dans l'issue ou la commande. Pour des opérations déterministes dans le dépôt principal, ne pas demander ce skill.

---

## Étape 1 — Identifier le worktree actif

```bash
castor worktree:list
```

Le nom du worktree (`<nom>`) est affiché dans la colonne "Nom".
La branche en cours est affiché dans la colonne "Branche".

---

## Environnement worktree

| Élément | Valeur |
|---------|--------|
| Répertoire legacy | `prevarisc-worktree-<nom>/` |
| Répertoire Symfony | `prevarisc-migration-worktree-<nom>/` |
| Conteneur applicatif (PHP 7.1) | `worktree-app-1` |
| Conteneur outils lint (PHP 8.1) | `worktree-platau-1` |
| Conteneur base de données | `worktree-db-1` |
| URL de l'application | http://localhost:7081 |
| Port DB | 33062 |
| Compose file | `compose.worktree-standalone.yaml` |
| Project name Docker | `worktree` |

> ⚠️ **Ne jamais utiliser** les conteneurs ou commandes de la stack principale (`compose.dev.yaml`, conteneur `app` du projet principal, port 7080) pour un travail de worktree.

---

## Étape 2 — Implémenter

Travailler exclusivement dans les répertoires worktree :

- Modifications Zend legacy → `prevarisc-worktree-<nom>/`
- Code Symfony / migrations Doctrine → `prevarisc-migration-worktree-<nom>/`

**Règle SQL :** Les modifications de base de données passent exclusivement par des **migrations Doctrine** dans `prevarisc-migration-worktree-<nom>/migrations/` — jamais dans `prevarisc/sql/`.

---

## Étape 3 — Lint (commandes worktree dédiées)

Les commandes `castor symfony:*` ciblent la stack principale. Utiliser les équivalents worktree :

```bash
castor worktree:validate   # ⭐ Validation complète (PHPStan + CS dry-run + tests) — s'arrête au premier échec
castor worktree:analyse    # PHPStan uniquement
castor worktree:cs         # Rector + PHP-CS-Fixer + Twig-CS-Fixer (corrige les fichiers)
castor worktree:cs --dry-run  # Vérification sans modification (mode CI)
castor worktree:test       # PHPUnit (hors @group functional)
castor worktree:test --all # PHPUnit complet
```

Ces commandes ciblent automatiquement le conteneur `worktree-platau-1` (PHP 8.1) pour les outils d'analyse et `worktree-app-1` (PHP 7.1) pour les tests.

---

## Étape 4 — Gestion des dépendances Composer

Pour ajouter ou retirer une dépendance dans le worktree, utiliser le conteneur worktree :

```bash
docker compose --project-name worktree --file compose.worktree-standalone.yaml \
  exec -w /var/www/html/prevarisc-migration worktree-app-1 \
  composer require <package>

docker compose --project-name worktree --file compose.worktree-standalone.yaml \
  exec -w /var/www/html/prevarisc-migration worktree-app-1 \
  composer remove <package>
```

> Modifier `prevarisc-migration-worktree-<nom>/composer.json`, **pas** `prevarisc-migration/composer.json`.

---

## Étape 5 — Mettre à jour le rapport de suivi

**Obligation** : après chaque commit réussi (lint ✅), mettre à jour le rapport permanent
**sans attendre de second prompt**.

### Quel fichier mettre à jour ?

| Type de tâche | Fichier à mettre à jour |
|---------------|------------------------|
| Refactor Symfony (REF-*) | `docs/RAPPORT_SYMFONY.md` — colonne **Statut** du tableau de synthèse (§10) |
| Modernisation de dépendances/outillage | `docs/RAPPORT_MODERNISATION.md` — tableau `## Suivi d'avancement` |

### Mise à jour dans `RAPPORT_SYMFONY.md`

Modifier la colonne **Statut** de la ligne correspondante dans le tableau de synthèse :

```markdown
| REF-XXX | ... | 🔄 En cours — `MonRepository` ✅ PR#NNN · `AutreRepository` ⏳ |
```

Statuts disponibles : `✅ Validé` · `🔄 En cours` · `⏳ À faire` · `⚠️ À analyser` · `❌ N/A`

### Mise à jour dans `RAPPORT_MODERNISATION.md`

Ajouter une ligne dans le tableau `## Suivi d'avancement` :

```markdown
| XX-N | Description courte de la tâche | ✅ Fait | `<sha_court>` |
```

Et mettre à jour la ligne `> **Worktree actif :**` si nécessaire.

---

## Étape 6 — Commit

> ⚠️ **Ne jamais commiter `REVIEW_REPORT.md`** — ce fichier est temporaire, généré pendant la relecture uniquement. Il est dans `.gitignore` et doit être supprimé une fois la revue terminée.

```bash
# Dans prevarisc-worktree-<nom>/ (si legacy modifié)
git -C prevarisc-worktree-<nom> add .
git -C prevarisc-worktree-<nom> commit -m "fix(<scope>): description

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"

# Dans prevarisc-migration-worktree-<nom>/ (si Symfony modifié)
git -C prevarisc-migration-worktree-<nom> add .
git -C prevarisc-migration-worktree-<nom> commit -m "fix(<scope>): description

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

---

## Étape 6 — Push et ouverture des PRs

```bash
# La branche est du type <type>/<nom>, la PR cible release/2.8
git -C prevarisc-worktree-<nom> push origin <branche>
git -C prevarisc-migration-worktree-<nom> push origin <branche>
```

---

## Types de tâches typiques

| Type commit | Cas d'usage |
|-------------|-------------|
| `refactor` | Amélioration du code sans changement de comportement |
| `fix` | Correction de bug |
| `chore` | Mise à jour de dépendances, nettoyage |
| `feat` | Petite fonctionnalité isolée (nouveau bloc dashboard, privilege) |

---

## Résumé visuel

```
Stack principale (compose.dev.yaml)       Stack worktree (compose.worktree-standalone.yaml)
──────────────────────────────────────    ─────────────────────────────────────────────────
prevarisc/              → app (7.1)        prevarisc-worktree-<nom>/     → worktree-app-1 (7.1)
prevarisc-migration/    → platau (8.1)     prevarisc-migration-worktree-<nom>/ → worktree-platau-1 (8.1)
http://localhost:7080                      http://localhost:7081
port DB: 33061                             port DB: 33062
castor symfony:validate                    castor worktree:validate
```

Les deux stacks sont complètement **isolées** et peuvent tourner simultanément.
